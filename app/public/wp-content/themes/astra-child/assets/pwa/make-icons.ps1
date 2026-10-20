# Генератор PNG-иконок PWA «БерегСити» из SVG-источников.
# Источники: icon-any.svg (знак 68% холста), icon-maskable.svg (знак 56% — safe-zone 80%).
# Рендер — headless Chrome/Edge без сторонних библиотек (ImageMagick на машине нет).
#
# Запуск:  powershell -ExecutionPolicy Bypass -File make-icons.ps1
# Файлы кладутся рядом со скриптом: icon-180.png, icon-192.png, icon-512.png, icon-512-maskable.png
# Размеры проверяются по заголовку PNG (IHDR) сразу после рендера.

$ErrorActionPreference = 'Stop'
$here = Split-Path -Parent $MyInvocation.MyCommand.Path

$chrome = @(
  'C:\Program Files\Google\Chrome\Application\chrome.exe',
  'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
  'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
  'C:\Program Files\Microsoft\Edge\Application\msedge.exe'
) | Where-Object { Test-Path $_ } | Select-Object -First 1

if (-not $chrome) {
  throw 'Не найден Chrome/Edge для headless-рендера.'
}

$jobs = @(
  @{ Svg = 'icon-any.svg';      Size = 192; Out = 'icon-192.png' },
  @{ Svg = 'icon-any.svg';      Size = 512; Out = 'icon-512.png' },
  @{ Svg = 'icon-maskable.svg'; Size = 512; Out = 'icon-512-maskable.png' },
  @{ Svg = 'icon-any.svg';      Size = 180; Out = 'icon-180.png' }
)

foreach ($job in $jobs) {
  $svg = [System.IO.File]::ReadAllText((Join-Path $here $job.Svg), [System.Text.Encoding]::UTF8)
  $svg = $svg.Replace('width="512" height="512"', ('width="{0}" height="{0}"' -f $job.Size))

  $html = '<!doctype html><html><head><meta charset="utf-8"><style>html,body{margin:0;padding:0;background:#c48067;overflow:hidden}svg{display:block}</style></head><body>' + $svg + '</body></html>'

  $tmpHtml = Join-Path $env:TEMP ('bc-pwa-' + $job.Out + '.html')
  [System.IO.File]::WriteAllText($tmpHtml, $html, (New-Object System.Text.UTF8Encoding($false)))

  $out = Join-Path $here $job.Out
  if (Test-Path $out) { Remove-Item $out -Force }

  & $chrome --headless=new --disable-gpu --hide-scrollbars --force-device-scale-factor=1 `
    ('--window-size={0},{0}' -f $job.Size) ('--screenshot=' + $out) ('file:///' + $tmpHtml.Replace('\', '/')) | Out-Null

  Start-Sleep -Milliseconds 500
  if (-not (Test-Path $out)) { throw ('Не создан файл: ' + $job.Out) }

  $b = [System.IO.File]::ReadAllBytes($out)
  $w = ([int]$b[16] -shl 24) -bor ([int]$b[17] -shl 16) -bor ([int]$b[18] -shl 8) -bor [int]$b[19]
  $h = ([int]$b[20] -shl 24) -bor ([int]$b[21] -shl 16) -bor ([int]$b[22] -shl 8) -bor [int]$b[23]
  '{0}: {1}x{2}, {3} КБ' -f $job.Out, $w, $h, [math]::Round($b.Length / 1KB, 1)
}
