<?php
declare(strict_types=1);

// Simple Web UI to manage config.json and run sync jobs
// Requirements: PHP built-in server or any web server
// Start (dev): php -S 0.0.0.0:8080 -t web

const CONFIG_PATH = __DIR__ . '/../config.json';
const SYNC_PATH = __DIR__ . '/../sync.php';

function load_config(): array {
	if (!file_exists(CONFIG_PATH)) {
		return [
			'global' => [
				'watch' => false,
				'interval' => 3,
				'mirror' => false,
				'checksum' => false,
				'preserveTimes' => true,
				'followSymlinks' => false,
				'dryRun' => false,
				'verbose' => true,
				'quiet' => false,
				'lock' => true,
				'exclude' => [],
				'excludeFrom' => []
			],
			'jobs' => []
		];
	}
	$json = file_get_contents(CONFIG_PATH);
	if ($json === false) {
		return ['global' => [], 'jobs' => []];
	}
	$cfg = json_decode($json, true);
	return is_array($cfg) ? $cfg : ['global' => [], 'jobs' => []];
}

function save_config(array $cfg): bool {
	$json = json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	return file_put_contents(CONFIG_PATH, $json) !== false;
}

function run_sync(?string $job = null, bool $once = true): array {
	$cmd = escapeshellcmd(PHP_BINARY) . ' ' . escapeshellarg(SYNC_PATH) . ' --config ' . escapeshellarg(dirname(__DIR__) . '/config.json');
	if ($once) {
		$cmd .= ' --once';
	}
	if ($job !== null && $job !== '') {
		$cmd .= ' --job ' . escapeshellarg($job);
	}
	$cmd .= ' 2>&1';
	$out = [];
	$code = 0;
	exec($cmd, $out, $code);
	return [$code, implode("\n", $out)];
}

$cfg = load_config();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$message = '';
$output = '';

if ($action === 'save') {
	$data = $_POST['config_json'] ?? '';
	$decoded = json_decode($data, true);
	if (is_array($decoded)) {
		if (save_config($decoded)) {
			$message = 'Configuração salva com sucesso.';
			$cfg = $decoded;
		} else {
			$message = 'Erro ao salvar configuração.';
		}
	} else {
		$message = 'JSON inválido.';
	}
} elseif ($action === 'run_all') {
	[$code, $output] = run_sync(null, true);
	$message = 'Execução concluída (todos os jobs). Código: ' . $code;
} elseif ($action === 'run_job') {
	$job = $_POST['job_name'] ?? '';
	[$code, $output] = run_sync($job, true);
	$message = 'Execução concluída (job: ' . htmlspecialchars($job, ENT_QUOTES) . '). Código: ' . $code;
}

?><!doctype html>
<html lang="pt-br">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>PHP Folder Sync - Plataforma</title>
	<style>
		body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, 'Helvetica Neue', Arial, 'Noto Sans', 'Liberation Sans', sans-serif; margin: 24px; }
		h1 { font-size: 22px; margin-bottom: 12px; }
		.container { display: grid; grid-template-columns: 1fr; gap: 16px; }
		.card { border: 1px solid #ddd; border-radius: 8px; padding: 16px; }
		textarea { width: 100%; height: 360px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', monospace; font-size: 13px; }
		.row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
		button { padding: 8px 12px; border: 1px solid #bbb; background: #f6f6f6; border-radius: 6px; cursor: pointer; }
		button:hover { background: #efefef; }
		.select { padding: 6px 8px; }
		pre { background: #0b1021; color: #d8e0ff; padding: 12px; border-radius: 6px; overflow: auto; max-height: 360px; }
		.note { color: #666; font-size: 12px; }
		.alert { padding: 10px 12px; border-radius: 6px; background: #f2f8ff; border: 1px solid #cfe3ff; }
	</style>
</head>
<body>
	<h1>PHP Folder Sync - Plataforma</h1>
	<?php if ($message): ?>
		<div class="alert"><?php echo htmlspecialchars($message, ENT_QUOTES); ?></div>
	<?php endif; ?>
	<div class="container">
		<div class="card">
			<h3>Configuração (config.json)</h3>
			<form method="post">
				<input type="hidden" name="action" value="save" />
				<textarea name="config_json"><?php echo htmlspecialchars(json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES); ?></textarea>
				<div class="row">
					<button type="submit">Salvar</button>
					<span class="note">O arquivo é salvo em: <?php echo htmlspecialchars(CONFIG_PATH, ENT_QUOTES); ?></span>
				</div>
			</form>
		</div>
		<div class="card">
			<h3>Executar sincronização</h3>
			<form method="post" class="row">
				<input type="hidden" name="action" value="run_all" />
				<button type="submit">Rodar todos os jobs (uma vez)</button>
			</form>
			<form method="post" class="row" style="margin-top: 8px;">
				<input type="hidden" name="action" value="run_job" />
				<select name="job_name" class="select">
					<?php foreach (($cfg['jobs'] ?? []) as $job): $name = (string)($job['name'] ?? ''); ?>
						<option value="<?php echo htmlspecialchars($name, ENT_QUOTES); ?>"><?php echo htmlspecialchars($name, ENT_QUOTES); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="submit">Rodar job selecionado (uma vez)</button>
			</form>
			<?php if ($output): ?>
				<h4>Saída</h4>
				<pre><?php echo htmlspecialchars($output, ENT_QUOTES); ?></pre>
			<?php endif; ?>
		</div>
	</div>
</body>
</html>
