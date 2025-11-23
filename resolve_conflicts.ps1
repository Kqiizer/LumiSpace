# Script para resolver conflictos de merge en pos.css
$filePath = "pos/assets/pos.css"
$content = Get-Content $filePath -Raw -Encoding UTF8

# Patrón para encontrar conflictos: <<<<<<< HEAD ... ======= ... >>>>>>>
$pattern = '(?s)<<<<<<< HEAD\s*(.*?)\s*=======\s*(.*?)\s*>>>>>>> [^\r\n]+'

# Reemplazar cada conflicto manteniendo solo la versión de HEAD (primera parte)
$resolved = $content -replace $pattern, '$1'

# Guardar el archivo resuelto
Set-Content -Path $filePath -Value $resolved -Encoding UTF8 -NoNewline

Write-Host "Conflictos resueltos en $filePath"

