<?php

namespace App\Libraries;

use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\{Str, Facades\File};
use LSNepomuceno\LaravelA1PdfSign\Exception\{FileNotFoundException, InvalidPdfFileException, InvalidPdfSignModeTypeException};

class SignaturePdf
{
  /**
   * @var string
   */
  const
    MODE_DOWNLOAD = 'MODE_DOWNLOAD',
    MODE_RESOURCE = 'MODE_RESOURCE';

  /**
   * @var \setasign\Fpdi\Tcpdf\Fpdi
   */
  private Fpdi $pdf;

  /**
   * @var \LSNepomuceno\LaravelA1PdfSign\ManageCert
   */
  private ManageCert $cert;

  /**
   * @var string
   */
  private string $pdfPath, $mode, $fileName;

  /**
   * @var array|null
   */
  private ?array $image = null;

  /**
   * @var array
   */
  private array $info = [];

  /**
   * @var boolean
   */
  private bool $hasSignedSuffix;

  /**
   * __construct
   *
   * @param  string $pdfPath
   * @param  \LSNepomuceno\LaravelA1PdfSign\ManageCert $cert
   * @param  string $mode self::MODE_RESOURCE
   * @param  string $fileName null
   * @param  bool $hasSignedSuffix false
   * @throws \Throwable
   * @throws \LSNepomuceno\LaravelA1PdfSign\Exception\{FileNotFoundException,InvalidPdfSignModeTypeException}
   * @return void
   */
  public function __construct(string $pdfPath, ManageCert $cert, string $mode = self::MODE_RESOURCE, string $fileName = '', bool $hasSignedSuffix = true)
  {
    /**
     * @throws FileNotFoundException
     */
    if (!File::exists($pdfPath)) throw new FileNotFoundException($pdfPath);

    /**
     * @throws InvalidPdfSignModeTypeException
     */
    if (!in_array($mode, [self::MODE_RESOURCE, self::MODE_DOWNLOAD])) throw new InvalidPdfSignModeTypeException($mode);

    $this->cert = $cert;

    // Throws exception on invalidate certificate
    try {
      $this->cert->validate();
    } catch (\Throwable $th) {
      throw $th;
    }

    $this->setFileName($fileName)
      ->setHasSignedSuffix($hasSignedSuffix);

    $this->mode    = $mode;
    $this->pdfPath = $pdfPath;
    $this->setPdf();
  }

  /**
   * setInfo - Set signature info
   *
   * @param  string|null $name
   * @param  string|null $location
   * @param  string|null $reason
   * @param  string|null $contactInfo
   *
   * @return \LSNepomuceno\LaravelA1PdfSign\SignaturePdf
   */
  public function setInfo(
    ?string $name = null,
    ?string $location = null,
    ?string $reason = null,
    ?string $contactInfo = null
  ): SignaturePdf {
    $info        = [];
    $name        && ($info['Name'] = $name);
    $location    && ($info['Location'] = $location);
    $reason      && ($info['Reason'] = $reason);
    $contactInfo && ($info['ContactInfo'] = $contactInfo);
    $this->info  = $info;
    return $this;
  }

  /**
   * getPdfInstance - Return current Fdpi object instance
   *
   * @return \setasign\Fpdi\Tcpdf\Fpdi
   */
  public function getPdfInstance(): Fpdi
  {
    return $this->pdf;
  }

  /**
   * setPdf - Set PDF settings
   *
   * @param  string $orientation  PDF_PAGE_ORIENTATION,
   * @param  string $unit  PDF_UNIT,
   * @param  string $pageFormat  PDF_PAGE_FORMAT,
   * @param  bool   $unicode  true,
   * @param  string $encoding  'UTF-8'
   *
   * @return \LSNepomuceno\LaravelA1PdfSign\SignaturePdf
   */
  public function setPdf(
    string $orientation = 'P',
    string $unit = 'mm',
    string $pageFormat = 'A4',
    bool $unicode = true,
    string $encoding = 'UTF-8'
  ): SignaturePdf {
    $this->pdf = new Fpdi($orientation, $unit, $pageFormat, $unicode, $encoding);
    return $this;
  }

  /**
   * setImage - Defines an image as a signature identifier
   *
   * @param  string $imagePath - Support only for PNG images
   * @param  float  $pageX
   * @param  float  $pageY
   * @param  float  $imageH
   * @param  float  $imageW
   *
   * @return \LSNepomuceno\LaravelA1PdfSign\SignaturePdf
   */
  public function setImage(
    string $imagePath,
    float  $pageX = 155,
    float  $pageY = 250,
    float  $imageW = 50,
    float  $imageH = 0,
    int  $page = -1
  ): SignaturePdf {
    $this->image = compact('imagePath', 'pageX', 'pageY', 'imageW', 'imageH', 'page');
    return $this;
  }

  /**
   * setFileName - Set output file name
   *
   * @param  string $fileName
   * @return \LSNepomuceno\LaravelA1PdfSign\SignaturePdf
   */
  public function setFileName(string $fileName): SignaturePdf
  {
    $ext = explode('.', $fileName);
    $ext = end($ext);
    $this->fileName = str_replace(".{$ext}", '', $fileName);
    return $this;
  }

  /**
   * setHasSignedSuffix - Set if the output file has a "signed" suffix
   *
   * @param  bool $hasSignedSuffix
   * @return \LSNepomuceno\LaravelA1PdfSign\SignaturePdf
   */
  public function setHasSignedSuffix(bool $hasSignedSuffix): SignaturePdf
  {
    $this->hasSignedSuffix = $hasSignedSuffix;
    return $this;
  }

  /**
   * signature - Sign a PDF file
   *
   * @return mixed
   */
  public function signature($output_file_name)
  {
    $pagecount = $this->pdf->setSourceFile($this->pdfPath);

    for ($i = 1; $i <= $pagecount; $i++) {
      $tplidx = $this->pdf->importPage($i);
      $this->pdf->SetPrintHeader(false);
      $this->pdf->SetPrintFooter(false);
      $this->pdf->AddPage();
      $this->pdf->useTemplate($tplidx);
    }

    $certificate = $this->cert->getCert()->original;
    $password    = $this->cert->getCert()->password;

    $this->pdf->setSignature(
      $certificate,
      $certificate,
      $password,
      '',
      3,
      $this->info,
      'A' // Authorize certificate
    );

    if ($this->image) {
      extract($this->image);
      $this->pdf->Image($imagePath, $pageX, $pageY, $imageW, $imageH, 'PNG');
      $this->pdf->setSignatureAppearance($pageX, $pageY, $imageW, $imageH, $page);
    }

    if (empty($this->fileName)) $this->fileName = Str::orderedUuid();
    if ($this->hasSignedSuffix) $this->fileName .= '_signed';

    $this->fileName .= '.pdf';

    $output = $output_file_name;
    File::delete($output);

    // Required to receive data from the server, such as timestamp and allocation hash.
    if (!File::exists($output)) File::put($output, $this->pdf->output($this->fileName, 'S'));

    switch ($this->mode) {
      case self::MODE_RESOURCE:
        $content = File::get($output);
        // File::delete([$output]);
        return $output;
        break;

      case self::MODE_DOWNLOAD:
      default:
        return response()->download($output)->deleteFileAfterSend();
        break;
    }
  }
}
