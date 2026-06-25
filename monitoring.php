<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'status' => 'error',
        'message' => 'Método não permitido. Use POST.'
    ]);

    exit;
}

const MIN_INSTANCES = 2;

$instance_type = $_POST['instance_type'] ?? null;
$instance_id = $_POST['instance_id'] ?? null;
$private_ip = $_POST['private_ip'] ?? null;
$asg_name = $_POST['asg_name'] ?? null;
$region = $_POST['region'] ?? null;

if (!$asg_name || !$region) {
    http_response_code(400);

    echo json_encode([
        'status' => 'error',
        'message' => 'asg_name e region são obrigatórios.'
    ]);

    exit;
}

$asg_name_safe = escapeshellarg($asg_name);
$region_safe = escapeshellarg($region);

$asg_json = shell_exec("aws autoscaling describe-auto-scaling-groups --auto-scaling-group-names $asg_name_safe --region $region_safe 2>/dev/null");

if (!$asg_json) {
    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao consultar o Auto Scaling Group.'
    ]);

    exit;
}

$asg = json_decode($asg_json, true);

if (!isset($asg["AutoScalingGroups"][0])) {
    http_response_code(404);

    echo json_encode([
        'status' => 'error',
        'message' => 'Auto Scaling Group não encontrado.'
    ]);

    exit;
}

$group = $asg["AutoScalingGroups"][0];

$desired = $group["DesiredCapacity"] ?? "N/A";
$instances = $group["Instances"] ?? [];
$min = $group["MinSize"] ?? "N/A";
$max = $group["MaxSize"] ?? "N/A";

$running_instances = array_filter($instances, function ($instance) {
    return isset($instance["LifecycleState"]) && $instance["LifecycleState"] === "InService";
});

$running = count($running_instances);

$status_msg = ($running >= MIN_INSTANCES)
    ? "Auto Scaling está funcionando!"
    : "Auto Scaling ainda não demonstrou escala.";

$status_class = ($running >= MIN_INSTANCES) ? "success" : "error";

$instance_metrics = [];

// Obtém métricas de CPU do CloudWatch para a instância atual
foreach ($instances as $instance) {
    if (isset($instance["InstanceId"]) && $instance["InstanceId"] === $instance_id) {
        $instance_id = $instance["InstanceId"];
        $end = gmdate("Y-m-d\TH:i:s\Z");
        $start = gmdate("Y-m-d\TH:i:s\Z", time() - 300);

        $cmd = "aws cloudwatch get-metric-statistics " .
            "--namespace AWS/EC2 " .
            "--metric-name CPUUtilization " .
            "--dimensions Name=InstanceId,Value=" . escapeshellarg($instance_id) . " " .
            "--statistics Average " .
            "--start-time $start " .
            "--end-time $end " .
            "--period 60 " .
            "--region " . escapeshellarg($region) . " " .
            "--output json 2>/dev/null";
            
        $json = shell_exec($cmd);
        $data = json_decode($json, true);
        $CPU_AVG = 0;

        if (isset($data["Datapoints"]) && !empty($data["Datapoints"])) {
            usort($data["Datapoints"], function($a, $b) {
                return strtotime($b["Timestamp"]) - strtotime($a["Timestamp"]);
            });
            
            $CPU_AVG = $data["Datapoints"][0]["Average"] ?? 0;
        }

        $instance_metrics[] = [
            "InstanceId" => $instance.InstanceId,
            "AvailabilityZone" => $instance.AvailabilityZone,
            "HealthStatus" => $instance.HealthStatus
            "LifecycleState" => $instance.LifecycleState,
            "CPU_AVG" => $CPU_AVG
        ];
    }
}

echo json_encode([
    'status' => 'success',
    'status_msg' => $status_msg,
    'status_class' => $status_class,
    'desired' => $desired,
    'min' => $min,
    'max' => $max,
    'running' => $running,
    'instances' => array_values($instance_metrics)
]);
?>