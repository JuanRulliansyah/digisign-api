<?php

namespace App\Libraries;

use Illuminate\Http\UploadedFile;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\{Fluent, Str, Facades\File};
use Illuminate\Contracts\Encryption\{DecryptException, EncryptException};
use Symfony\Component\Process\{Process, Exception\ProcessFailedException};
use LSNepomuceno\LaravelA1PdfSign\Exception\{
  CertificateOutputNotFounfException,
  FileNotFoundException,
  InvalidCertificateContentException,
  InvalidPFXException,
  Invalidx509PrivateKeyException,
  ProcessRunTimeException
};

class ManageCert
{
  /**
   * @var string
   */
  private string $tempDir, $originalCertContent, $password, $hashKey;

  /**
   * @var bool
   */
  private bool $preservePfx = false;

  /**
   * @var array
   */
  private array $parsedData;

  /**
   * @var \OpenSSLCertificate|resource|boolean
   */
  private $certContent;

  /**
   * @var string
   */
  const CIPHER = 'aes-128-cbc';

  /**
   * @var \Illuminate\Encryption\Encrypter
   */
  private Encrypter $encrypter;

  /**
   * __construct
   *
   * @return void
   */
  public function __construct()
  {
    $this->tempDir = a1TempDir();

    $this->generateHashKey()->setEncrypter();

    if (!File::exists($this->tempDir)) File::makeDirectory($this->tempDir);
  }

  /**
   * setPreservePfx - Defines whether the pfx file should be preserved after processing
   *
   * @param boolean $preservePfx true
   *
   * @return \LSNepomuceno\LaravelA1PdfSign\ManageCert
   */
  public function setPreservePfx(bool $preservePfx = true): ManageCert
  {
    $this->preservePfx = $preservePfx;
    return $this;
  }

  /**
   * fromPfx - Generate CRT certificate from PFX file
   *
   * @param  string $pfxPath
   * @param  string $password
   *
   * @throws \LSNepomuceno\LaravelA1PdfSign\Exception\{CertificateOutputNotFounfException,FileNotFoundException,InvalidPFXException,ProcessRunTimeException}
   * @throws \Symfony\Component\Process\Exception\ProcessFailedException
   *
   * @return \LSNepomuceno\LaravelA1PdfSign\ManageCert
   */
  public function fromPfx(string $pfxPath, string $password): ManageCert
  {
    /**
     * @throws InvalidPFXException
     */
    if (!Str::of($pfxPath)->lower()->endsWith('.p12')) throw new InvalidPFXException($pfxPath);

    /**
     * @throws FileNotFoundException
     */
    if (!File::exists($pfxPath)) throw new FileNotFoundException($pfxPath);

    $this->password = $password;
    $output  = a1TempDir(true, '.crt');
    $openssl = "openssl pkcs12 -in {$pfxPath} -out {$output} -nodes -password pass:{$this->password}";

    try {
      $process = Process::fromShellCommandline($openssl);
      $process->run();

      while ($process->isRunning());

      /**
       * @throws ProcessRunTimeException
       */
      if (!$process->isSuccessful()) throw new ProcessRunTimeException($process->getErrorOutput());

      $process->stop(1);
    } catch (ProcessFailedException $exception) {
      throw $exception;
    }

    /**
     * @throws CertificateOutputNotFounfException
     */
    if (!File::exists($output)) throw new CertificateOutputNotFounfException;

    $content = File::get($output);

    $filesToBeDelete = [$output];

    !$this->preservePfx && ($filesToBeDelete[] = $pfxPath);

    // File::delete($filesToBeDelete);

    return $this->setCertContent($content);
  }

  /**
   * fromUpload - Generates a new certificate from the uploaded pfx file
   *
   * @param  \Illuminate\Http\UploadedFile $uploadedPfx
   * @param  string $password
   *
   * @return \LSNepomuceno\LaravelA1PdfSign\ManageCert
   */
  public function fromUpload(UploadedFile $uploadedPfx, string $password): ManageCert
  {
    $pfxTemp = a1TempDir(true);

    if (File::exists($pfxTemp)) {
      $pfxTemp = microtime() . $pfxTemp;
    }

    File::put($pfxTemp, $uploadedPfx->get());

    $this->fromPfx($pfxTemp, $password);

    File::delete($pfxTemp);

    return $this;
  }

  /**
   * setCertContent - Set a valid OpenSSLCertificate certificate content
   *
   * @param  string $certContent
   * @return \LSNepomuceno\LaravelA1PdfSign\ManageCert
   */
  public function setCertContent(string $certContent): ManageCert
  {
    $this->originalCertContent = $certContent;
    $this->certContent         = openssl_x509_read($certContent);
    $this->parsedData          = openssl_x509_parse($this->certContent, false);
    $this->validate();
    return $this;
  }

  /**
   * validate
   *
   * @throws \LSNepomuceno\LaravelA1PdfSign\Exception\{InvalidCertificateContentException,Invalidx509PrivateKeyException}
   *
   * @return void
   */
  public function validate(): void
  {
    /**
     * @throws InvalidCertificateContentException
     */
    if (!$this->certContent) {
      $this->invalidate();
      throw new InvalidCertificateContentException;
    }

    /**
     * @throws Invalidx509PrivateKeyException
     */
    if (!openssl_x509_check_private_key($this->certContent, $this->originalCertContent)) {
      $this->invalidate();
      throw new Invalidx509PrivateKeyException;
    }
  }

  /**
   * invalidate - Makes the certificate invalid
   *
   * @return void
   */
  private function invalidate(): void
  {
    $this->originalCertContent = '';
    $this->certContent         = false;
    $this->parsedData          = [];
    $this->password            = '';
  }

  /**
   * getCert - Returns objects containing resources and the original certificate string.
   *
   * @return \Illuminate\Support\Fluent
   */
  public function getCert(): Fluent
  {
    return new Fluent([
      'original' => $this->originalCertContent,
      'openssl'  => $this->certContent,
      'data'     => $this->parsedData,
      'password' => $this->password
    ]);
  }

  /**
   * getTempDir - Returns tempDir path
   *
   * @return string
   */
  public function getTempDir(): string
  {
    return $this->tempDir;
  }

  /**
   * generateHashKey - Generates a new hash key, based on self::CIPHER const
   * @see self::CIPHER
   *
   * @return \LSNepomuceno\LaravelA1PdfSign\ManageCert
   */
  public function generateHashKey(): ManageCert
  {
    $this->hashKey = Encrypter::generateKey(self::CIPHER);

    $this->setEncrypter();

    return $this;
  }

  /**
   * setHashKey - Set a new hash key
   *
   * @param  string $hashKey
   *
   * @return \LSNepomuceno\LaravelA1PdfSign\ManageCert
   */
  public function setHashKey(string $hashKey): ManageCert
  {
    $this->hashKey = $hashKey;

    $this->setEncrypter();

    return $this;
  }

  /**
   * setEncrypter - Starts the Encrypter instance
   *
   * @return void
   */
  private function setEncrypter(): void
  {
    $this->encrypter = new Encrypter($this->hashKey, self::CIPHER);
  }

  /**
   * getHashKey - Returns the hash key
   *
   * @return string
   */
  public function getHashKey(): string
  {
    return $this->encrypter->getKey();
  }

  /**
   * getEncrypter - Returns the Encrypter instance
   *
   * @return \Illuminate\Encryption\Encrypter
   */
  public function getEncrypter(): Encrypter
  {
    return  $this->encrypter;
  }

  /**
   * encryptBase64BlobString - Encrypt a base64 string
   *
   * @param  string $blobString
   * @throws \Illuminate\Contracts\Encryption\EncryptException
   *
   * @return string
   */
  public function encryptBase64BlobString(string $blobString): string
  {
    try {
      return $this->encrypter->encryptString(base64_encode($blobString));
    } catch (EncryptException $th) {
      throw $th;
    }
  }

  /**
   * decryptBase64BlobString - Decrypt a base64 string
   *
   * @param  string $encryptedBlobString
   * @throws \Illuminate\Contracts\Encryption\DecryptException
   *
   * @return string
   */
  public function decryptBase64BlobString(string $encryptedBlobString): string
  {
    try {
      $string = $this->encrypter->decryptString($encryptedBlobString);
      return base64_decode($string);
    } catch (DecryptException $th) {
      throw $th;
    }
  }

  /**
   * makeDebugCertificate - Generate fake certificate for debug reasons
   *
   * @param bool $returnPathAndPass false
   * @param bool $wrongPass false
   *
   * @throws \LSNepomuceno\LaravelA1PdfSign\Exception\ProcessRunTimeException
   * @throws \Symfony\Component\Process\Exception\ProcessFailedException
   *
   * @return \LSNepomuceno\LaravelA1PdfSign\ManageCert|string
   */
  public function makeDebugCertificate(bool $returnPathAndPass = false, bool $wrongPass = false)
  {
    $pass = 123456;
    $name = $this->tempDir . Str::orderedUuid();

    $genCommands = [
      "openssl req -x509 -newkey rsa:4096 -sha256 -keyout {$name}.key -out {$name}.crt -subj \"/CN=Test Certificate /OU=LucasNepomuceno\" -days 600 -passout pass:{$pass}",
      "openssl pkcs12 -export -name test.com -out {$name}.pfx -inkey {$name}.key -in {$name}.crt -passin pass:{$pass} -passout pass:{$pass}"
    ];

    foreach ($genCommands as $command) {
      try {
        $process = Process::fromShellCommandline($command);
        $process->run();

        while ($process->isRunning());

        /**
         * @throws ProcessRunTimeException
         */
        if (!$process->isSuccessful()) throw new ProcessRunTimeException($process->getErrorOutput());

        $process->stop(1);
      } catch (ProcessFailedException $exception) {
        throw $exception;
      }
    }

    File::delete(["{$name}.key", "{$name}.crt"]);

    return $returnPathAndPass ? ["{$name}.pfx", $pass] : $this->fromPfx("{$name}.pfx", $wrongPass ? 'wrongPass' : $pass);
  }
}
