[CmdletBinding()]
param(
	[switch]$Force
)

$ErrorActionPreference = 'Stop'

$repositoryRoot = Split-Path -Parent $PSScriptRoot
$sourceDirectory = Join-Path $repositoryRoot 'plugin'
$mainPluginFile = Join-Path $sourceDirectory 'meu-delivery.php'
$readmeFile = Join-Path $sourceDirectory 'readme.txt'

if (-not (Test-Path -LiteralPath $mainPluginFile -PathType Leaf)) {
	throw 'Arquivo principal não encontrado em plugin/meu-delivery.php.'
}

$mainSource = Get-Content -LiteralPath $mainPluginFile -Raw
$coreFile = Join-Path $sourceDirectory 'includes/Core/Plugin.php'
$coreSource = Get-Content -LiteralPath $coreFile -Raw
$headerVersion = [regex]::Match($mainSource, '(?m)^\s*\* Version:\s*([^\s]+)').Groups[1].Value
$constantVersion = [regex]::Match($coreSource, "const VERSION = '([^']+)'\;").Groups[1].Value

if (-not $headerVersion -or $headerVersion -ne $constantVersion) {
	throw 'A versão do cabeçalho do plugin não corresponde à constante VERSION do núcleo.'
}

if (-not (Test-Path -LiteralPath $readmeFile -PathType Leaf)) {
	throw 'Arquivo readme.txt não encontrado em plugin/.'
}

$rootFolderName = 'meu-delivery-2.0'
$distributionDirectory = Join-Path $repositoryRoot 'dist'
[System.IO.Directory]::CreateDirectory($distributionDirectory) | Out-Null
$archivePath = Join-Path $distributionDirectory ('meu-delivery-2.0-' + $headerVersion + '.zip')

if (Test-Path -LiteralPath $archivePath -PathType Leaf) {
	if (-not $Force) {
		throw 'O ZIP de destino já existe. Use -Force para substituí-lo.'
	}

	$resolvedDistribution = [System.IO.Path]::GetFullPath($distributionDirectory).TrimEnd('\') + '\'
	$resolvedArchive = [System.IO.Path]::GetFullPath($archivePath)
	if (-not $resolvedArchive.StartsWith($resolvedDistribution, [System.StringComparison]::OrdinalIgnoreCase)) {
		throw 'O arquivo de destino não está dentro de dist/.'
	}

	Remove-Item -LiteralPath $resolvedArchive -Force
}

Add-Type -AssemblyName System.IO.Compression
$fileStream = [System.IO.File]::Open(
	$archivePath,
	[System.IO.FileMode]::CreateNew,
	[System.IO.FileAccess]::ReadWrite,
	[System.IO.FileShare]::None
)
$archive = [System.IO.Compression.ZipArchive]::new(
	$fileStream,
	[System.IO.Compression.ZipArchiveMode]::Create
)

try {
	$sourceRoot = [System.IO.Path]::GetFullPath($sourceDirectory).TrimEnd('\')
	$files = Get-ChildItem -LiteralPath $sourceDirectory -Recurse -File | Sort-Object FullName
	if (-not $files) {
		throw 'A pasta plugin/ não contém arquivos para empacotar.'
	}

	foreach ($file in $files) {
		$relativePath = $file.FullName.Substring($sourceRoot.Length).TrimStart('\', '/')
		$relativePath = $relativePath.Replace('\', '/')
		$entryName = $rootFolderName + '/' + $relativePath
		$entry = $archive.CreateEntry($entryName, [System.IO.Compression.CompressionLevel]::Optimal)
		$inputStream = [System.IO.File]::OpenRead($file.FullName)
		try {
			$entryStream = $entry.Open()
			try {
				$inputStream.CopyTo($entryStream)
			} finally {
				$entryStream.Dispose()
			}
		} finally {
			$inputStream.Dispose()
		}
	}
} finally {
	$archive.Dispose()
}

$verificationArchive = [System.IO.Compression.ZipFile]::OpenRead($archivePath)
try {
	$entryNames = @($verificationArchive.Entries | ForEach-Object { $_.FullName })
	$expectedPrefix = $rootFolderName + '/'
	if (-not $entryNames -or ($entryNames | Where-Object { -not $_.StartsWith($expectedPrefix, [System.StringComparison]::Ordinal) })) {
		throw 'A estrutura do ZIP contém caminhos fora da pasta raiz esperada.'
	}

	if ($entryNames -notcontains ($expectedPrefix + 'meu-delivery.php')) {
		throw 'O arquivo principal não foi incluído na raiz instalável.'
	}

	if ($entryNames -contains ($expectedPrefix + $rootFolderName + '/meu-delivery.php')) {
		throw 'O ZIP contém uma pasta duplicada.'
	}
} finally {
	$verificationArchive.Dispose()
}

$hash = (Get-FileHash -LiteralPath $archivePath -Algorithm SHA256).Hash
Write-Output ('Pacote WordPress criado: ' + $archivePath)
Write-Output ('Versão: ' + $headerVersion)
Write-Output ('Arquivos incluídos: ' + $files.Count)
Write-Output ('SHA-256: ' + $hash)
