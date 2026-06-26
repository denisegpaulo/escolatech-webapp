<?php
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);

        echo json_encode([
            'status' => 'error',
            'message' => 'Erro ao iniciar Carga de CPU.'
        ]);

        exit;
    }

    // Verifica se existe algum teste de carga em andamento
    $running = trim(shell_exec("pgrep -x stress-ng"));

    if (!empty($running)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Teste de Carga de CPU em andamento.',
            'pids' => explode("\n", $running)
        ]);

        exit;
    }

    // Configurações do teste de carga de CPU
    $cpu_workers = 2;
    $cpu_load = 90;
    $duration_seconds = 360;
    $log_file = '/tmp/stress-ng.log';

    // Verifica se stress-ng foi instalado
    $stress_ng_path = trim(shell_exec('command -v stress-ng'));

    if (empty($stress_ng_path)) {
        http_response_code(500);

        echo json_encode([
            'status' => 'error',
            'message' => 'stress-ng precisa ser instalado nesta instancia EC2.'
        ]);

        exit;
    }

    // Inicia stress-ng asyncronamente e captura o PID
    $command = sprintf(
        'nohup %s --aggressive --cpu %d --cpu-load %d --cpu-method matrixprod --timeout %d > %s 2>&1 & echo $!',
        escapeshellcmd($stress_ng_path),
        $cpu_workers,
        $cpu_load,
        $duration_seconds,
        escapeshellarg($log_file)
    );

    $pid = trim(shell_exec($command));

    echo json_encode([
        'status' => 'success',
        'message' => sprintf('Testando Carga de CPU por %d segundos.', $duration_seconds),
        'duration_seconds' => $duration_seconds,
        'cpu_workers' => $cpu_workers,
        'log_file' => $log_file,
        'pid' => $pid
    ]);
?>