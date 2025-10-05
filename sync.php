#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * PHP Folder Synchronizer (Linux-friendly)
 *
 * Features:
 * - One-way sync (source -> dest)
 * - Mirror mode (deletes extras in destination)
 * - Exclude patterns via --exclude and --exclude-from
 * - Dry-run, verbose/quiet modes
 * - Comparison by size+mtime (default) or checksum (--checksum)
 * - Optional watch loop with --watch and --interval
 * - Locking to prevent concurrent runs (flock)
 * - Optionally preserve source modified times on copied files
 *
 * Usage: run with --help
 */

final class Cli
{
    public static function out(string $message, bool $newline = true): void
    {
        if ($newline) {
            fwrite(STDOUT, $message . PHP_EOL);
        } else {
            fwrite(STDOUT, $message);
        }
    }

    public static function err(string $message, bool $newline = true): void
    {
        if ($newline) {
            fwrite(STDERR, $message . PHP_EOL);
        } else {
            fwrite(STDERR, $message);
        }
    }
}

final class Options
{
    public ?string $source = null;
    public ?string $dest = null;
    public bool $mirror = false;
    /** @var string[] */
    public array $excludePatterns = [];
    /** @var string[] */
    public array $excludeFromFiles = [];
    public bool $dryRun = false;
    public bool $useChecksum = false;
    public bool $watch = false;
    public int $intervalSeconds = 3;
    public bool $preserveTimes = false;
    public bool $verbose = true;
    public bool $quiet = false;
    public bool $lock = true;
    public bool $followSymlinks = false;
    public ?string $configPath = null;
    public ?string $label = null;
    /** @var string[] */
    public array $onlyJobs = [];
    public bool $forceOnce = false;

    public static function parseFromArgv(array $argv): self
    {
        $long = [
            'source:',
            'dest:',
            'config:',
            'job:',
            'jobs:',
            'once',
            'mirror',
            'exclude:',
            'exclude-from:',
            'dry-run',
            'checksum',
            'watch',
            'interval:',
            'preserve-times',
            'verbose',
            'quiet',
            'no-lock',
            'follow-symlinks',
            'help',
        ];

        $opt = getopt('', $long);

        if (isset($opt['help'])) {
            self::printHelpAndExit();
        }

        $instance = new self();

        $cfg = $opt['config'] ?? null;
        if (is_string($cfg) && $cfg !== '') {
            $instance->configPath = self::normalizePath($cfg);
        }

        $src = $opt['source'] ?? null;
        $dst = $opt['dest'] ?? null;
        if ($instance->configPath === null) {
            if (!is_string($src) || !is_string($dst) || $src === '' || $dst === '') {
                Cli::err('Erro: use --source e --dest, ou --config para múltiplas pastas.');
                self::printHelpAndExit(2);
            }
            $instance->source = self::normalizePath($src);
            $instance->dest = self::normalizePath($dst);
        }

        $instance->mirror = array_key_exists('mirror', $opt);
        $instance->dryRun = array_key_exists('dry-run', $opt);
        $instance->useChecksum = array_key_exists('checksum', $opt);
        $instance->watch = array_key_exists('watch', $opt);
        $instance->preserveTimes = array_key_exists('preserve-times', $opt);
        $instance->verbose = array_key_exists('quiet', $opt) ? false : true;
        $instance->quiet = array_key_exists('quiet', $opt);
        $instance->lock = array_key_exists('no-lock', $opt) ? false : true;
        $instance->followSymlinks = array_key_exists('follow-symlinks', $opt);

        if (isset($opt['interval'])) {
            $val = is_array($opt['interval']) ? end($opt['interval']) : $opt['interval'];
            $ival = is_numeric($val) ? (int) $val : 3;
            $instance->intervalSeconds = max(1, $ival);
        }

        if (isset($opt['exclude'])) {
            $instance->excludePatterns = is_array($opt['exclude']) ? array_values(array_filter(array_map('strval', $opt['exclude']), 'strlen')) : [strval($opt['exclude'])];
        }
        if (isset($opt['exclude-from'])) {
            $instance->excludeFromFiles = is_array($opt['exclude-from']) ? array_values(array_filter(array_map('strval', $opt['exclude-from']), 'strlen')) : [strval($opt['exclude-from'])];
        }

        // Job filters
        $jobsRaw = [];
        if (isset($opt['job'])) {
            $jobsRaw = array_merge($jobsRaw, is_array($opt['job']) ? $opt['job'] : [$opt['job']]);
        }
        if (isset($opt['jobs'])) {
            $jobsRaw = array_merge($jobsRaw, is_array($opt['jobs']) ? $opt['jobs'] : [$opt['jobs']]);
        }
        $jobsList = [];
        foreach ($jobsRaw as $jr) {
            foreach (preg_split('/,/', (string)$jr) ?: [] as $piece) {
                $name = trim($piece);
                if ($name !== '') {
                    $jobsList[] = $name;
                }
            }
        }
        $instance->onlyJobs = array_values(array_unique($jobsList));
        $instance->forceOnce = array_key_exists('once', $opt);

        return $instance;
    }

    private static function normalizePath(string $path): string
    {
        // Resolve ~ and relative segments where possible
        if ($path[0] === '~') {
            $home = getenv('HOME') ?: '';
            if ($home !== '') {
                $path = $home . substr($path, 1);
            }
        }
        return rtrim($path, "/\");
    }

    public static function printHelpAndExit(int $code = 0): never
    {
        Cli::out(<<<'TXT'
Sincronizador de Pastas (PHP)

Uso:
  php sync.php --source /origem --dest /destino [opções]
  php sync.php --config /caminho/config.json [opções_globais]

Opções:
  --job NOME               (com --config) Executa apenas o(s) job(s) com este nome (repita ou use vírgula)
  --once                   (com --config) Executa uma vez, ignorando watch do config
  --mirror                 Espelha destino (remove arquivos que não existem na origem)
  --exclude Padrão         Exclui caminhos (pode repetir). Usa fnmatch sobre caminho relativo
  --exclude-from ARQ       Lê padrões de exclusão de arquivo (um por linha, # para comentários)
  --dry-run                Não altera nada; apenas mostra o que faria
  --checksum               Compara por checksum (sha1) ao invés de tamanho+mtime
  --watch                  Laço contínuo; repete a sincronização periodicamente
  --interval N             Intervalo (segundos) entre execuções quando em --watch (padrão: 3)
  --preserve-times         Preserva mtime do arquivo de origem ao copiar
  --verbose                Saída detalhada (padrão)
  --quiet                  Saída mínima
  --no-lock                Desabilita trava de execução (flock)
  --follow-symlinks        Segue symlinks (por padrão são ignorados)
  --help                   Mostra esta ajuda

Exemplos:
  php sync.php --source /data/in --dest /data/out --mirror --exclude "*.tmp" --dry-run
  php sync.php --source ./src --dest ./backup --checksum --verbose
  php sync.php --source /a --dest /b --watch --interval 5

Config JSON (múltiplas pastas):
  {
    "global": {
      "watch": true,
      "interval": 3,
      "mirror": true,
      "exclude": ["*.tmp", "cache/"]
    },
    "jobs": [
      {
        "name": "LobbyAssets",
        "sources": ["/srv/lobby/assets"],
        "destinations": ["/srv/server1/assets", "/srv/server2/assets"]
      },
      {
        "name": "Configs",
        "sources": ["/srv/configs"],
        "destinations": ["/srv/server1/configs", "/srv/server2/configs"],
        "interval": 10
      }
    ]
  }
TXT);
        exit($code);
    }
}

final class Excluder
{
    /** @var string[] */
    private array $patterns;

    public function __construct(array $patterns)
    {
        $this->patterns = array_values(array_filter(array_map(static function ($p) {
            $p = trim((string) $p);
            if ($p === '' || $p[0] === '#') {
                return '';
            }
            return $p;
        }, $patterns), 'strlen'));
    }

    public static function fromOptions(Options $o): self
    {
        $patterns = $o->excludePatterns;
        foreach ($o->excludeFromFiles as $file) {
            if (is_file($file)) {
                $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#')) {
                        continue;
                    }
                    $patterns[] = $line;
                }
            }
        }
        return new self($patterns);
    }

    public function isExcluded(string $relativePath, bool $isDir): bool
    {
        $normalized = ltrim(str_replace(['\\',], '/', $relativePath), '/');
        foreach ($this->patterns as $pattern) {
            $pattern = str_replace('\\', '/', $pattern);
            $dirOnly = str_ends_with($pattern, '/');
            $pat = rtrim($pattern, '/');

            if ($dirOnly && !$isDir) {
                // Pattern só para diretórios
                continue;
            }

            // Tente casar no caminho completo relativo
            if (fnmatch($pat, $normalized, FNM_PATHNAME)) {
                return true;
            }
            // Tente casar por segmento (ex: **/*.tmp)
            if (fnmatch($pat, basename($normalized))) {
                return true;
            }
        }
        return false;
    }
}

final class FileComparator
{
    public static function isDifferent(string $src, string $dst, bool $useChecksum): bool
    {
        if (!file_exists($dst)) {
            return true;
        }
        if (is_dir($src) && is_dir($dst)) {
            return false;
        }
        if (is_dir($src) !== is_dir($dst)) {
            return true;
        }
        if (is_link($src)) {
            // Links: apenas compara alvo textual
            $srcTarget = readlink($src);
            $dstTarget = is_link($dst) ? readlink($dst) : null;
            return $srcTarget !== $dstTarget;
        }
        if ($useChecksum) {
            $s1 = @sha1_file($src) ?: '';
            $s2 = @sha1_file($dst) ?: '';
            return $s1 !== $s2;
        }
        $sSrc = filesize($src);
        $sDst = filesize($dst);
        if ($sSrc !== $sDst) {
            return true;
        }
        $tSrc = filemtime($src) ?: 0;
        $tDst = filemtime($dst) ?: 0;
        // Copia se a origem for mais nova (tolerância de 1s)
        return ($tSrc - $tDst) > 1;
    }
}

final class Locker
{
    private $handle = null;

    public function acquire(string $source, string $dest): void
    {
        $hash = sha1($source . '|' . $dest);
        $path = sys_get_temp_dir() . '/php-folder-sync-' . $hash . '.lock';
        $h = fopen($path, 'c');
        if ($h === false) {
            throw new RuntimeException('Falha ao criar lock file: ' . $path);
        }
        if (!flock($h, LOCK_EX | LOCK_NB)) {
            throw new RuntimeException('Outra instância já está em execução para este par de pastas.');
        }
        $this->handle = $h;
    }

    public function release(): void
    {
        if ($this->handle) {
            flock($this->handle, LOCK_UN);
            fclose($this->handle);
            $this->handle = null;
        }
    }
}

final class Sync
{
    private Options $o;
    private Excluder $ex;

    public function __construct(Options $o)
    {
        $this->o = $o;
        $this->ex = Excluder::fromOptions($o);
    }

    public function runOnce(): void
    {
        $src = $this->o->source;
        $dst = $this->o->dest;

        if (!is_dir($src)) {
            throw new RuntimeException('Pasta de origem não encontrada: ' . $src);
        }
        if (!file_exists($dst)) {
            $this->log("+ mkdir: $dst");
            if (!$this->o->dryRun) {
                if (!mkdir($dst, 0755, true) && !is_dir($dst)) {
                    throw new RuntimeException('Falha ao criar destino: ' . $dst);
                }
            }
        }
        if (!is_dir($dst)) {
            throw new RuntimeException('Destino não é uma pasta: ' . $dst);
        }

        $sourceSet = $this->collectSource($src);
        $this->copyAndUpdate($sourceSet);

        if ($this->o->mirror) {
            $this->deleteExtraneous($sourceSet);
        }
    }

    /**
     * @return array<string, array{abs:string, isDir:bool, isLink:bool}>
     */
    private function collectSource(string $srcRoot): array
    {
        $result = [];
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcRoot, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $path => $info) {
            /** @var SplFileInfo $info */
            $relative = ltrim(str_replace(['\\', $this->o->source . '/'], ['/', ''], $path), '/');
            $isDir = $info->isDir();
            $isLink = is_link($path);
            if (!$this->o->followSymlinks && $isLink) {
                $this->log("~ skip symlink: $relative");
                continue;
            }
            if ($this->ex->isExcluded($relative, $isDir)) {
                $this->log("~ excluded: $relative");
                if ($isDir) {
                    // Pula subtree
                    $it->next();
                }
                continue;
            }
            $result[$relative] = [
                'abs' => $path,
                'isDir' => $isDir,
                'isLink' => $isLink,
            ];
        }
        return $result;
    }

    /**
     * @param array<string, array{abs:string, isDir:bool, isLink:bool}> $sourceSet
     */
    private function copyAndUpdate(array $sourceSet): void
    {
        foreach ($sourceSet as $relative => $meta) {
            $srcPath = $meta['abs'];
            $dstPath = $this->o->dest . '/' . $relative;

            if ($meta['isDir']) {
                if (!is_dir($dstPath)) {
                    $this->log("+ mkdir: $relative");
                    if (!$this->o->dryRun) {
                        if (!mkdir($dstPath, 0755, true) && !is_dir($dstPath)) {
                            throw new RuntimeException('Falha ao criar diretório: ' . $dstPath);
                        }
                    }
                }
                continue;
            }

            if ($meta['isLink'] && $this->o->followSymlinks) {
                $target = readlink($srcPath);
                if ($target === false) {
                    $this->log("! erro readlink: $relative");
                    continue;
                }
                if (!is_link($dstPath) || readlink($dstPath) !== $target) {
                    $this->log("+ symlink: $relative -> $target");
                    if (!$this->o->dryRun) {
                        @unlink($dstPath);
                        if (!@symlink($target, $dstPath)) {
                            throw new RuntimeException('Falha ao criar symlink: ' . $dstPath);
                        }
                    }
                } else {
                    $this->log("= ok symlink: $relative");
                }
                continue;
            }

            $dstDir = dirname($dstPath);
            if (!is_dir($dstDir)) {
                $this->log("+ mkdir: " . ltrim(str_replace($this->o->dest . '/', '', $dstDir), '/'));
                if (!$this->o->dryRun) {
                    if (!mkdir($dstDir, 0755, true) && !is_dir($dstDir)) {
                        throw new RuntimeException('Falha ao criar diretório: ' . $dstDir);
                    }
                }
            }

            if (FileComparator::isDifferent($srcPath, $dstPath, $this->o->useChecksum)) {
                $this->log("~ copy: $relative");
                if (!$this->o->dryRun) {
                    if (!@copy($srcPath, $dstPath)) {
                        throw new RuntimeException('Falha ao copiar: ' . $relative);
                    }
                    if ($this->o->preserveTimes) {
                        @touch($dstPath, filemtime($srcPath) ?: time());
                    }
                }
            } else {
                $this->log("= ok: $relative");
            }
        }
    }

    /**
     * @param array<string, array{abs:string, isDir:bool, isLink:bool}> $sourceSet
     */
    private function deleteExtraneous(array $sourceSet): void
    {
        $srcKeys = array_fill_keys(array_keys($sourceSet), true);
        $dstRoot = $this->o->dest;
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dstRoot, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $path => $info) {
            /** @var SplFileInfo $info */
            $relative = ltrim(str_replace(['\\', $dstRoot . '/'], ['/', ''], $path), '/');
            $isDir = $info->isDir();

            // Respeita exclusões: se seria excluído na origem, não mexe
            if ($this->ex->isExcluded($relative, $isDir)) {
                $this->log("~ keep (excluded): $relative");
                continue;
            }

            if (!isset($srcKeys[$relative])) {
                if ($isDir) {
                    $this->log("- rmdir: $relative");
                    if (!$this->o->dryRun) {
                        @rmdir($path);
                    }
                } else {
                    $this->log("- unlink: $relative");
                    if (!$this->o->dryRun) {
                        @unlink($path);
                    }
                }
            }
        }
    }

    private function log(string $message): void
    {
        if ($this->o->quiet) {
            return;
        }
        if ($this->o->verbose) {
            $prefix = $this->o->label ? ($this->o->label . ' ') : '';
            Cli::out($prefix . $message);
        }
    }
}

final class ConfigRunner
{
    private Options $baseOptions;

    public function __construct(Options $base)
    {
        $this->baseOptions = $base;
    }

    /**
     * Executa sincronização baseada em configuração JSON.
     * Suporta múltiplas origens e destinos por job e mirror seguro por união.
     */
    public function run(): int
    {
        $path = $this->baseOptions->configPath;
        if ($path === null || !is_file($path)) {
            throw new RuntimeException('Arquivo de configuração não encontrado: ' . ($path ?? 'null'));
        }
        $json = file_get_contents($path);
        if ($json === false) {
            throw new RuntimeException('Falha ao ler configuração: ' . $path);
        }
        /** @var array<string,mixed> $cfg */
        $cfg = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $global = (array)($cfg['global'] ?? []);
        $jobs = (array)($cfg['jobs'] ?? []);
        if ($jobs === []) {
            throw new RuntimeException('Configuração não contém jobs.');
        }

        $watchGlobal = (bool)($global['watch'] ?? $this->baseOptions->watch);
        $intervalGlobal = (int)($global['interval'] ?? $this->baseOptions->intervalSeconds);
        $mirrorGlobal = (bool)($global['mirror'] ?? $this->baseOptions->mirror);
        $checksumGlobal = (bool)($global['checksum'] ?? $this->baseOptions->useChecksum);
        $preserveTimesGlobal = (bool)($global['preserveTimes'] ?? $this->baseOptions->preserveTimes);
        $followSymlinksGlobal = (bool)($global['followSymlinks'] ?? $this->baseOptions->followSymlinks);
        $dryRunGlobal = (bool)($global['dryRun'] ?? $this->baseOptions->dryRun);
        $verboseGlobal = (bool)($global['verbose'] ?? $this->baseOptions->verbose);
        $quietGlobal = (bool)($global['quiet'] ?? $this->baseOptions->quiet);
        $lockGlobal = !isset($global['lock']) ? $this->baseOptions->lock : (bool)$global['lock'];
        $excludeGlobal = array_values(array_filter((array)($global['exclude'] ?? []), 'is_string'));
        $excludeFromGlobal = array_values(array_filter((array)($global['excludeFrom'] ?? []), 'is_string'));

        // Normaliza jobs e aplica defaults globais
        $jobsNorm = [];
        foreach ($jobs as $job) {
            $name = (string)($job['name'] ?? 'job');
            $sources = array_values(array_filter((array)($job['sources'] ?? []), 'is_string'));
            $dests = array_values(array_filter((array)($job['destinations'] ?? []), 'is_string'));
            if ($sources === [] || $dests === []) {
                Cli::err("[{$name}] Ignorado: sources/destinations vazios");
                continue;
            }
            $jobsNorm[] = [
                'name' => $name,
                'sources' => array_map(static fn($p) => rtrim((string)$p, "/\\"), $sources),
                'destinations' => array_map(static fn($p) => rtrim((string)$p, "/\\"), $dests),
                'interval' => (int)($job['interval'] ?? $intervalGlobal),
                'mirror' => (bool)($job['mirror'] ?? $mirrorGlobal),
                'checksum' => (bool)($job['checksum'] ?? $checksumGlobal),
                'preserveTimes' => (bool)($job['preserveTimes'] ?? $preserveTimesGlobal),
                'followSymlinks' => (bool)($job['followSymlinks'] ?? $followSymlinksGlobal),
                'dryRun' => (bool)($job['dryRun'] ?? $dryRunGlobal),
                'verbose' => (bool)($job['verbose'] ?? $verboseGlobal),
                'quiet' => (bool)($job['quiet'] ?? $quietGlobal),
                'lock' => !isset($job['lock']) ? $lockGlobal : (bool)$job['lock'],
                'exclude' => array_merge($excludeGlobal, array_values(array_filter((array)($job['exclude'] ?? []), 'is_string'))),
                'excludeFrom' => array_merge($excludeFromGlobal, array_values(array_filter((array)($job['excludeFrom'] ?? []), 'is_string'))),
            ];
        }

        $only = $this->baseOptions->onlyJobs;
        if (!empty($only)) {
            $jobsNorm = array_values(array_filter($jobsNorm, static function ($jn) use ($only) {
                return in_array($jn['name'], $only, true);
            }));
        }

        if ($watchGlobal && !$this->baseOptions->forceOnce) {
            Cli::out('Iniciando modo watch (config). Ctrl+C para sair.');
            $nextRunAt = [];
            $now = microtime(true);
            foreach ($jobsNorm as $idx => $jn) {
                $nextRunAt[$idx] = $now; // roda imediatamente
            }
            while (true) {
                $now = microtime(true);
                $soonest = null;
                foreach ($jobsNorm as $idx => $jn) {
                    if ($now + 1e-6 >= ($nextRunAt[$idx] ?? 0)) {
                        $this->executeJob($jn);
                        $interval = max(1, (int)$jn['interval']);
                        $nextRunAt[$idx] = microtime(true) + $interval;
                    }
                    $soonest = $soonest === null ? ($nextRunAt[$idx] ?? null) : min($soonest, ($nextRunAt[$idx] ?? $soonest));
                }
                $sleep = 1;
                if ($soonest !== null) {
                    $delta = (int)ceil(max(0.0, $soonest - microtime(true)));
                    $sleep = max(1, $delta);
                }
                sleep($sleep);
            }
        } else {
            foreach ($jobsNorm as $jn) {
                $this->executeJob($jn);
            }
        }

        return 0;
    }

    /**
     * Executa um job: copia/atualiza de todas as sources para cada destination e, se mirror,
     * realiza exclusão baseada na união das sources.
     * @param array<string,mixed> $jn
     */
    private function executeJob(array $jn): void
    {
        $label = (string)$jn['name'];
        $excluder = $this->buildExcluder((array)$jn['exclude'], (array)$jn['excludeFrom']);
        foreach ((array)$jn['destinations'] as $dst) {
            // 1) Copia/atualiza de cada source para o destino (mirror desativado por enquanto)
            foreach ((array)$jn['sources'] as $src) {
                $opt = new Options();
                $opt->source = rtrim((string)$src, "/\\");
                $opt->dest = rtrim((string)$dst, "/\\");
                $opt->mirror = false; // mirror será feito após a união
                $opt->useChecksum = (bool)$jn['checksum'];
                $opt->preserveTimes = (bool)$jn['preserveTimes'];
                $opt->followSymlinks = (bool)$jn['followSymlinks'];
                $opt->dryRun = (bool)$jn['dryRun'];
                $opt->verbose = (bool)$jn['verbose'];
                $opt->quiet = (bool)$jn['quiet'];
                $opt->lock = (bool)$jn['lock'];
                $opt->excludePatterns = (array)$jn['exclude'];
                $opt->excludeFromFiles = (array)$jn['excludeFrom'];
                $opt->label = "[{$label}]";

                $locker = new Locker();
                try {
                    if ($opt->lock && $opt->source !== null && $opt->dest !== null) {
                        $locker->acquire($opt->source, $opt->dest);
                    }
                    $sync = new Sync($opt);
                    $sync->runOnce();
                } catch (Throwable $t) {
                    Cli::err("[{$label}] Erro: " . $t->getMessage());
                } finally {
                    $locker->release();
                }
            }

            // 2) Se mirror ativo, calcula união das sources e remove excedentes no destino
            if ((bool)$jn['mirror']) {
                $union = [];
                foreach ((array)$jn['sources'] as $src) {
                    $set = $this->collectSourceRelativeSet((string)$src, $excluder, (bool)$jn['followSymlinks']);
                    foreach ($set as $rel => $_) {
                        $union[$rel] = true;
                    }
                }
                $this->deleteExtraneousByUnion((string)$dst, $excluder, $union, (bool)$jn['dryRun'], $label, (bool)$jn['quiet'], (bool)$jn['verbose']);
            }
        }
    }

    private function buildExcluder(array $excludePatterns, array $excludeFromFiles): Excluder
    {
        $o = new Options();
        $o->excludePatterns = array_values(array_filter(array_map('strval', $excludePatterns), 'strlen'));
        $o->excludeFromFiles = array_values(array_filter(array_map('strval', $excludeFromFiles), 'strlen'));
        return Excluder::fromOptions($o);
    }

    /**
     * Coleta o conjunto relativo de entradas da origem respeitando exclusões e symlinks.
     * @return array<string,bool>
     */
    private function collectSourceRelativeSet(string $srcRoot, Excluder $ex, bool $followSymlinks): array
    {
        $srcRoot = rtrim($srcRoot, "/\\");
        $result = [];
        if (!is_dir($srcRoot)) {
            return $result;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcRoot, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $path => $info) {
            /** @var SplFileInfo $info */
            $relative = ltrim(str_replace(['\\', $srcRoot . '/'], ['/', ''], (string)$path), '/');
            $isDir = $info->isDir();
            $isLink = is_link((string)$path);
            if (!$followSymlinks && $isLink) {
                continue;
            }
            if ($ex->isExcluded($relative, $isDir)) {
                continue;
            }
            $result[$relative] = true;
        }
        return $result;
    }

    /**
     * Remove do destino o que não está presente na união das sources (respeitando exclusões).
     * @param array<string,bool> $unionSet
     */
    private function deleteExtraneousByUnion(string $destRoot, Excluder $ex, array $unionSet, bool $dryRun, string $label, bool $quiet, bool $verbose): void
    {
        $destRoot = rtrim($destRoot, "/\\");
        if (!is_dir($destRoot)) {
            return;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($destRoot, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $path => $info) {
            /** @var SplFileInfo $info */
            $relative = ltrim(str_replace(['\\', $destRoot . '/'], ['/', ''], (string)$path), '/');
            $isDir = $info->isDir();
            if ($ex->isExcluded($relative, $isDir)) {
                if ($verbose && !$quiet) {
                    Cli::out("[{$label}] ~ keep (excluded): $relative");
                }
                continue;
            }
            if (!isset($unionSet[$relative])) {
                if ($isDir) {
                    if ($verbose && !$quiet) {
                        Cli::out("[{$label}] - rmdir: $relative");
                    }
                    if (!$dryRun) {
                        @rmdir((string)$path);
                    }
                } else {
                    if ($verbose && !$quiet) {
                        Cli::out("[{$label}] - unlink: $relative");
                    }
                    if (!$dryRun) {
                        @unlink((string)$path);
                    }
                }
            }
        }
    }
}

function main(array $argv): int
{
    try {
        $o = Options::parseFromArgv($argv);
        if ($o->configPath !== null) {
            $runner = new ConfigRunner($o);
            return $runner->run();
        }

        $locker = new Locker();
        if ($o->lock && $o->source !== null && $o->dest !== null) {
            $locker->acquire($o->source, $o->dest);
        }

        $sync = new Sync($o);
        if ($o->watch) {
            Cli::out('Iniciando modo watch. Pressione Ctrl+C para sair.');
            while (true) {
                $started = microtime(true);
                try {
                    $sync->runOnce();
                } catch (Throwable $t) {
                    Cli::err('Erro durante sync: ' . $t->getMessage());
                }
                $elapsed = microtime(true) - $started;
                $sleep = max(0, $o->intervalSeconds - (int)round($elapsed));
                if ($sleep > 0) {
                    sleep($sleep);
                }
            }
        } else {
            $sync->runOnce();
        }

        $locker->release();
        return 0;
    } catch (Throwable $e) {
        Cli::err('Fatal: ' . $e->getMessage());
        return 1;
    }
}

exit(main($argv));


