# Regenerates carbon-ledger-brochure.pdf from carbon-ledger-brochure.html.
# Edit the HTML, run this, commit both. Requires Google Chrome.
#
#   powershell -ExecutionPolicy Bypass -File docs\brochure\build-pdf.ps1

$ErrorActionPreference = 'Stop'

$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$html = Join-Path $here 'carbon-ledger-brochure.html'
$pdf  = Join-Path $here 'carbon-ledger-brochure.pdf'

if (-not (Test-Path $html)) { throw "Source not found: $html" }

$chrome = @(
  "$env:ProgramFiles\Google\Chrome\Application\chrome.exe",
  "${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe",
  "$env:LOCALAPPDATA\Google\Chrome\Application\chrome.exe"
) | Where-Object { Test-Path $_ } | Select-Object -First 1

if (-not $chrome) { throw "Google Chrome not found. Install Chrome, or open the HTML and use Print > Save as PDF (A4, background graphics on, no margins)." }

# Chrome needs a throwaway profile, or it silently reuses a running instance.
$prof = Join-Path $env:TEMP ('brochure-pdf-' + [guid]::NewGuid().ToString('N'))

# Every value below can contain spaces (this repo's path does), and
# Start-Process does not quote them for you -- unquoted, Chrome parses the
# fragments after each space as additional URLs and refuses with
# "Multiple targets are not supported in headless mode."
$chromeArgs = @(
  '--headless=new'
  '--disable-gpu'
  '--no-first-run'
  '--no-default-browser-check'
  "--user-data-dir=`"$prof`""
  '--virtual-time-budget=20000'
  "--print-to-pdf=`"$pdf`""
  "`"$(([System.Uri]$html).AbsoluteUri)`""
)

if (Test-Path $pdf) { Remove-Item $pdf -Force }

Start-Process -FilePath $chrome -ArgumentList $chromeArgs -Wait -NoNewWindow | Out-Null

if (-not (Test-Path $pdf)) { throw "Chrome did not produce a PDF." }

$bytes = [System.IO.File]::ReadAllBytes($pdf)
$head = [System.Text.Encoding]::ASCII.GetString($bytes[0..4])
if ($head -ne '%PDF-') { throw "Output is not a valid PDF." }

$pageCount = ([regex]::Matches(
  [System.Text.Encoding]::GetEncoding(28591).GetString($bytes), '/Type\s*/Page[^s]')).Count

"PDF written: $pdf"
"Pages: $pageCount   Size: {0:N0} KB" -f ([math]::Round($bytes.Length / 1KB))
