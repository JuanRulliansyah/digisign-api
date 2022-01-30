<?php

class Sign {
    
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
}