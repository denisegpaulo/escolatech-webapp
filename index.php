<?php
$asg_name = "${asg_name}";
$alb_dns = "${alb_dns}";
$logo_url = "${logo_url}";

function meta($path) {
    $token = trim(shell_exec("curl -s -X PUT http://169.254.169.254/latest/api/token -H 'X-aws-ec2-metadata-token-ttl-seconds: 21600'"));
    return trim(shell_exec("curl -s -H 'X-aws-ec2-metadata-token: $token' http://169.254.169.254/latest/meta-data/$path"));
}

$az = meta("placement/availability-zone");
$instance_type = meta("instance-type");
$instance_id = meta("instance-id");
$private_ip = meta("local-ipv4");
$region = substr($az, 0, -1);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Horus Tech - Escola Tech</title>

<style>
:root {
    --bg: #05DB1F;
    --dark: #0D1030;
    --white: #E9E9FE;
    --orange: #FFBA1C;
    --purple: #65ABFF;
    --card: rgba(13,16,48,0.92);
}

* { box-sizing: border-box; }

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #050716;
    color: var(--white);
}

header {
    text-align: center;
    padding: 25px 20px 15px;
    border-bottom: 1px solid var(--purple);
    background: radial-gradient(circle at top, #101545, #050716 70%);
}

.logo {
    max-width: 330px;
    margin-bottom: 10px;
}

h1 {
    font-size: 34px;
    margin: 18px 0 10px;
}

.subtitle {
    font-size: 18px;
}

.client {
    margin: 15px auto;
    padding: 12px 25px;
    border: 1px solid var(--purple);
    border-radius: 12px;
    max-width: 620px;
    font-size: 18px;
}

.client strong { color: var(--orange); }

.topbar {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin: 18px 25px;
}

.box, .card {
    background: var(--card);
    border: 1px solid var(--purple);
    border-radius: 14px;
    padding: 18px;
    box-shadow: 0 0 18px rgba(101,171,255,0.18);
}

.grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin: 20px 25px;
}

.card h2, .panel h2 {
    color: var(--orange);
    font-size: 20px;
    margin-top: 0;
}

.card p, .box p {
    line-height: 1.6;
}

.value {
    color: var(--orange);
    font-weight: bold;
}

.green {
    color: #56ff65;
    font-weight: bold;
}

.red {
    color: #ff5656;
    font-weight: bold;
}

button {
    width: 100%;
    background: linear-gradient(90deg, #FFBA1C, #ff7800);
    border: none;
    border-radius: 10px;
    padding: 16px;
    font-weight: bold;
    font-size: 16px;
    color: #080915;
    cursor: pointer;
}

.success {
    margin-top: 14px;
    padding: 14px;
    border-radius: 10px;
    background: rgba(0,120,35,0.35);
    border: 1px solid #28ff55;
    color: #56ff65;
}

.error {
    margin-top: 14px;
    padding: 14px;
    border-radius: 10px;
    background: rgba(120,0,35,0.35);
    border: 1px solid #ff2855;
    color: #ff5665;
}

.monitor {
    display: grid;
    grid-template-columns: 2fr 1.2fr;
    gap: 14px;
    margin: 15px 25px 30px;
}

.panel {
    background: var(--card);
    border: 1px solid var(--purple);
    border-radius: 14px;
    padding: 20px;
}

.stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 18px;
}

.stat {
    border: 1px solid #44318f;
    border-radius: 12px;
    padding: 14px;
}

.stat strong {
    font-size: 28px;
    color: var(--orange);
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 12px;
}

th, td {
    padding: 11px;
    border-bottom: 1px solid #2a315c;
    text-align: left;
}

th {
    color: var(--purple);
}

.badge {
    background: #5325ff;
    color: white;
    border-radius: 8px;
    padding: 3px 8px;
    font-size: 12px;
}

.footer {
    text-align: center;
    padding: 22px;
    color: #bbb;
    border-top: 1px solid #291b50;
}

@media(max-width: 1100px) {
    .grid, .topbar, .monitor, .stats {
        grid-template-columns: 1fr;
    }
}

.progress-cell {
    font-weight: bold;
    text-align: center;
    padding: 8px;
    background: linear-gradient(to right, #56ff65 var(--progress), #ff5665 var(--progress));
}
</style>

<script defer>
    function stressInstance () {
        const stressButton = document.querySelector('button[name="stress"]');
        stressButton.textContent = "Iniciando Carga de CPU...";
        stressButton.disabled = true;

        fetch('stress.php', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                const stressMsgDiv = document.querySelector('#stressMsg');

                if (stressMsgDiv) {
                    stressMsgDiv.textContent = data.message;
                    stressMsgDiv.classList.add(data.status);

                    stressButton.textContent =  data.status == 'success' ? "Executando Teste de Carga de CPU" : "Iniciar Teste de Carga de CPU";

                    setTimeout(() => {
                        stressMsgDiv.textContent = '';
                        stressMsgDiv.classList.remove(data.status);

                        stressButton.textContent = "Iniciar Teste de Carga de CPU";
                    }, 5000);
                }
            })
            .catch(error => {
                console.error('Erro ao iniciar carga de CPU:', error);
                alert('Falha ao iniciar carga de CPU.');

                stressButton.textContent = "Iniciar Teste de Carga de CPU";
            })
            .finally(() => {
                stressButton.disabled = false;
            });
    }

    function monitorAutoScaling() {
        const formData = new FormData();
        formData.append('instance_type', '<?php echo $instance_type; ?>');
        formData.append('instance_id', '<?php echo $instance_id; ?>');
        formData.append('private_ip', '<?php echo $private_ip; ?>');
        formData.append('asg_name', '<?php echo $asg_name; ?>');
        formData.append('region', '<?php echo $region; ?>');

        fetch('monitoring.php', { 
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const stats = document.querySelectorAll('.stats .stat strong');
                    stats[0].textContent = data.desired;
                    stats[1].textContent = data.running;
                    stats[2].textContent = data.min;
                    stats[3].textContent = data.max;

                    status_msg = document.querySelector('#status_msg');
                    status_msg.textContent = data.status_msg;
                    status_msg.className = data.status_class;

                    const instancesTable = document.querySelector('#instancesTable');
                    const existingRows = instancesTable.querySelectorAll('tr:not(:first-child)');
                    existingRows.forEach(row => row.remove());

                    data.instances.forEach(instance => {
                        const row = instancesTable.insertRow();
                        row.insertCell(0).textContent = instance.InstanceId;
                        row.insertCell(1).textContent = instance.AvailabilityZone;

                        const healthStatusCell = row.insertCell(2);
                        healthStatusCell.textContent = instance.HealthStatus;
                        healthStatusCell.className = instance.HealthStatus == 'Healthy' ? 'green' : 'red';

                        const cpuAvgCell = row.insertCell(3);
                        cpuAvgCell.textContent = instance.CPU_AVG;
                        cpuAvgCell.className = 'progress-cell';
                        cpuAvgCell.style = '--progress: ' + instance.CPU_AVG + '%;';

                        row.insertCell(4).textContent = instance.LifecycleState;
                    });
                } else {
                    return Promise.reject(data.message);
                }
            })
            .catch(error => {
                console.error('Erro ao atualizar monitoramento do Auto Scaling:', error);
            });
    }

    setInterval(monitorAutoScaling, 5000);

</script>

</head>

<body>

<header>
    <img class="logo" src="<?php echo $logo_url; ?>" alt="Horus Tech Logo">
    <div class="subtitle">Cloud Infrastructure Demo - High Availability and Auto Scaling</div>
    <div class="client">Projeto desenvolvido para a <strong>Escola Tech</strong> como cliente.</div>
</header>

<div class="topbar">
    <div class="box">Ambiente: <span class="value">Produção Demo</span></div>
    <div class="box">Região AWS: <span class="value"><?php echo $region; ?></span></div>
    <div class="box">Load Balancer DNS:<br><span class="value"><?php echo $alb_dns; ?></span></div>
    <div class="box">Health: <span class="green">Targets Healthy</span></div>
</div>

<h1 style="text-align:center;">Horus Tech Web Application</h1>

<section class="grid">
    <div class="card">
        <h2>Descrição do Projeto</h2>
        <p>
            Este projeto demonstra uma arquitetura altamente disponível na AWS usando EC2,
            Application Load Balancer e Auto Scaling Group para atender a Escola Tech com
            escalabilidade, disponibilidade e tolerância a falhas.
        </p>
        <p>Cliente: <span class="value">Escola Tech</span></p>
    </div>

    <div class="card">
        <h2>Detalhes da Instância Atual</h2>
        <p>ID da Instância: <span class="value"><?php echo $instance_id; ?></span></p>
        <p>Zona de Disponibilidade: <span class="value"><?php echo $az; ?></span></p>
        <p>IP Privado: <span class="value"><?php echo $private_ip; ?></span></p>
        <p>Tipo de Instância: <span class="value"><?php echo $instance_type; ?></span></p>
    </div>

    <div class="card">
        <h2>Teste de Alta Disponibilidade</h2>
        <p>
            Atualize esta página usando o DNS do Load Balancer.
            Se aparecerem diferentes IDs de instância ou Zonas de Disponibilidade,
            o balanceamento entre múltiplas EC2 está funcionando.
        </p>
        <div class="success">HA Teste: Funcionando!</div>
    </div>

    <div class="card">
        <h2>Teste de Auto Scaling</h2>
        <p>
            Clique no botão para gerar carga de CPU nesta EC2.
            Isso ajuda a acionar a política de escala do Auto Scaling Group.
        </p>
        <div>
            <button name="stress" value="1" onclick="stressInstance()">Iniciar Teste de Carga de CPU</button>
        </div>
        <div id="stressMsg"></div>
    </div>
</section>

<section class="monitor">
    <div class="panel">
        <h2>Auto Scaling Group - Monitoramento em Tempo Real</h2>

        <div class="stats">
            <div class="stat">Capacidade Desejada<br><strong>--</strong> instâncias</div>
            <div class="stat">Em Execução<br><strong>--</strong> instâncias</div>
            <div class="stat">Capacidade Mínima<br><strong>--</strong> instâncias</div>
            <div class="stat">Capacidade Máxima<br><strong>--</strong> instâncias</div>
        </div>

        <h2>Instâncias do Auto Scaling Group</h2>
        <table id="instancesTable">
            <tr>
                <th>ID da Instância</th>
                <th>AZ</th>
                <th>Status</th>
                <th>Lifecycle</th>
            </tr>
        </table>

        <div id="status_msg"></div>
    </div>

    <div class="panel">
        <h2>Histórico de Escalabilidade</h2>
        <p>
            A escala é considerada ativa quando a carga de CPU aumenta e o Auto Scaling Group
            eleva a capacidade desejada, lançando novas instâncias automaticamente.
        </p>

        <h2>Eventos Recentes</h2>
        <p class="green">+ Novas instâncias aparecem aqui após a política de escala ser acionada.</p>
        <p>CPU média acima do limite configurado aciona escala para cima.</p>
        <p>Health Checks garantem que somente instâncias saudáveis recebam tráfego.</p>
    </div>
</section>

<div class="footer">
    Horus Tech © 2026 | Cloud • AWS • IA • Terraform • Prompt
</div>

</body>
</html>