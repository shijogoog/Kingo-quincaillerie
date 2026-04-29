<?php
session_start();
require_once 'config.php';
require_once 'database.php';
checkAuth();
$user = getCurrentUser();

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as n FROM articles"); mysqli_stmt_execute($stmt);
$totalArticles = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['n'];

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as n FROM articles WHERE quantite=0"); mysqli_stmt_execute($stmt);
$stockRupture = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['n'];

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as n FROM articles WHERE quantite>0 AND quantite<=stock_minimum"); mysqli_stmt_execute($stmt);
$stockFaible = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['n'];

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as n, COALESCE(SUM(montant_total),0) as ca FROM ventes WHERE MONTH(date_vente)=MONTH(NOW()) AND YEAR(date_vente)=YEAR(NOW()) AND statut='validée'");
mysqli_stmt_execute($stmt); $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$ventesMois = $row['n']; $caMois = $row['ca'];

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as n, COALESCE(SUM(montant_total),0) as ca FROM ventes WHERE MONTH(date_vente)=MONTH(DATE_SUB(NOW(),INTERVAL 1 MONTH)) AND YEAR(date_vente)=YEAR(DATE_SUB(NOW(),INTERVAL 1 MONTH)) AND statut='validée'");
mysqli_stmt_execute($stmt); $rowP = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$varVentes = $rowP['n']>0 ? round(($ventesMois-$rowP['n'])/$rowP['n']*100) : 0;
$varCA     = $rowP['ca']>0 ? round(($caMois-$rowP['ca'])/$rowP['ca']*100) : 0;

$stmt = mysqli_prepare($conn, "SELECT DATE_FORMAT(date_vente,'%b') as m, COUNT(*) as nb, COALESCE(SUM(montant_total),0) as ca FROM ventes WHERE date_vente>=DATE_SUB(NOW(),INTERVAL 6 MONTH) AND statut='validée' GROUP BY DATE_FORMAT(date_vente,'%Y-%m') ORDER BY MIN(date_vente) ASC");
mysqli_stmt_execute($stmt); $chart6m = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

$stmt = mysqli_prepare($conn, "SELECT a.categorie, SUM(dv.quantite) as total FROM details_vente dv JOIN articles a ON a.id=dv.article_id JOIN ventes v ON v.id=dv.vente_id WHERE v.statut='validée' AND MONTH(v.date_vente)=MONTH(NOW()) AND YEAR(v.date_vente)=YEAR(NOW()) GROUP BY a.categorie ORDER BY total DESC LIMIT 6");
mysqli_stmt_execute($stmt); $catData = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

$stmt = mysqli_prepare($conn, "SELECT a.nom, SUM(dv.quantite) as total FROM details_vente dv JOIN articles a ON a.id=dv.article_id GROUP BY dv.article_id ORDER BY total DESC LIMIT 5");
mysqli_stmt_execute($stmt); $topArticles = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

$stmt = mysqli_prepare($conn, "SELECT nom, quantite, stock_minimum, CASE WHEN quantite=0 THEN 'rupture' WHEN quantite<stock_minimum/2 THEN 'critique' ELSE 'faible' END as niveau FROM articles WHERE quantite<=stock_minimum ORDER BY quantite ASC LIMIT 6");
mysqli_stmt_execute($stmt); $alertesStock = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

$stmt = mysqli_prepare($conn, "SELECT v.id, v.date_vente, v.client_nom, v.montant_total, v.statut, u.nom as vendeur FROM ventes v JOIN users u ON u.id=v.utilisateur_id ORDER BY v.date_vente DESC LIMIT 5");
mysqli_stmt_execute($stmt); $dernieres = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

$stmt = mysqli_prepare($conn, "SELECT DATE_FORMAT(date_vente,'%d/%m') as j, COALESCE(SUM(montant_total),0) as ca FROM ventes WHERE date_vente>=DATE_SUB(NOW(),INTERVAL 10 DAY) AND statut='validée' GROUP BY DATE_FORMAT(date_vente,'%Y-%m-%d') ORDER BY MIN(date_vente) ASC");
mysqli_stmt_execute($stmt); $spark = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tableau de bord — <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-content">
<?php include 'includes/header.php'; ?>
<div class="page-content">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h3 mb-1">Tableau de bord</h1>
      <div style="font-size:.82rem;color:#94a3b8"><?= strftime('%A %d %B %Y') ?></div>
    </div>
    <a href="ajouter_vente.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nouvelle vente</a>
  </div>

  <!-- KPI -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="kpi-card blue">
        <div class="kpi-header">
          <div class="kpi-label">Articles</div>
          <div class="kpi-icon" style="background:#eff6ff;color:#3b82f6"><i class="bi bi-box-seam fs-5"></i></div>
        </div>
        <div class="kpi-value"><?= $totalArticles ?></div>
        <div class="kpi-sub">9 catégories actives</div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="kpi-card green">
        <div class="kpi-header">
          <div class="kpi-label">Ventes du mois</div>
          <div class="kpi-icon" style="background:#f0fdf4;color:#16a34a"><i class="bi bi-cart3 fs-5"></i></div>
        </div>
        <div class="kpi-value"><?= $ventesMois ?></div>
        <div class="kpi-sub">
          <span class="kpi-trend <?= $varVentes>=0?'up':'down' ?>">
            <i class="bi bi-arrow-<?= $varVentes>=0?'up':'down' ?>"></i> <?= abs($varVentes) ?>%
          </span>&nbsp;vs mois dernier
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="kpi-card orange">
        <div class="kpi-header">
          <div class="kpi-label">CA du mois</div>
          <div class="kpi-icon" style="background:#fff7ed;color:#f97316"><i class="bi bi-coin fs-5"></i></div>
        </div>
        <div class="kpi-value kpi-value-sm"><?= fcfa($caMois) ?></div>
        <div class="kpi-sub">
          <span class="kpi-trend <?= $varCA>=0?'up':'down' ?>">
            <i class="bi bi-arrow-<?= $varCA>=0?'up':'down' ?>"></i> <?= abs($varCA) ?>%
          </span>&nbsp;vs mois dernier
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="kpi-card red">
        <div class="kpi-header">
          <div class="kpi-label">Stock critique</div>
          <div class="kpi-icon" style="background:#fef2f2;color:#ef4444"><i class="bi bi-exclamation-triangle fs-5"></i></div>
        </div>
        <div class="kpi-value" style="color:#ef4444"><?= $stockFaible+$stockRupture ?></div>
        <div class="kpi-sub"><span style="color:#dc2626;font-weight:600"><?= $stockRupture ?> en rupture totale</span></div>
      </div>
    </div>
  </div>

  <!-- GRAPHIQUES -->
  <div class="row g-3 mb-4">
    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-header">
          Évolution des ventes
          <span class="badge-period">6 derniers mois</span>
        </div>
        <div class="card-body">
          <div class="chart-legend mb-3">
            <div class="chart-leg-item"><div class="chart-leg-dot" style="background:#1a2332"></div>Nombre de ventes</div>
            <div class="chart-leg-item"><div class="chart-leg-dot" style="background:#F4A261"></div>CA en FCFA</div>
          </div>
          <div style="position:relative;height:220px"><canvas id="ventesChart"></canvas></div>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header">
          Par catégorie
          <span class="badge-period">Ce mois</span>
        </div>
        <div class="card-body d-flex flex-column">
          <div style="position:relative;height:150px"><canvas id="catChart"></canvas></div>
          <div class="donut-legend mt-3">
            <?php
            $colors=['#1a2332','#F4A261','#3b82f6','#16a34a','#8b5cf6','#94a3b8'];
            $tot=array_sum(array_column($catData,'total'));
            foreach($catData as $i=>$c):
              $pct=$tot>0?round($c['total']/$tot*100):0;
            ?>
            <div class="donut-leg-row">
              <div class="donut-leg-sq" style="background:<?= $colors[$i%count($colors)] ?>"></div>
              <span class="donut-leg-lbl"><?= htmlspecialchars($c['categorie']) ?></span>
              <span class="donut-leg-pct"><?= $pct ?>%</span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- BOTTOM ROW -->
  <div class="row g-3">
    <!-- Top articles -->
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header">Top 5 articles vendus <span class="badge-period">Ce mois</span></div>
        <div class="card-body">
          <?php $max=$topArticles[0]['total']??1; $bc=['#1a2332','#F4A261','#3b82f6','#8b5cf6','#16a34a'];
          foreach($topArticles as $i=>$a): ?>
          <div class="topbar-row mb-3">
            <div class="topbar-label"><?= htmlspecialchars($a['nom']) ?></div>
            <div class="topbar-track"><div class="topbar-fill" style="width:<?= round($a['total']/$max*100) ?>%;background:<?= $bc[$i] ?>"></div></div>
            <div class="topbar-val"><?= $a['total'] ?> u.</div>
          </div>
          <?php endforeach; ?>
          <div class="sparkline-label mt-2">CA 10 derniers jours</div>
          <div style="position:relative;height:70px"><canvas id="sparkChart"></canvas></div>
        </div>
      </div>
    </div>

    <!-- Alertes -->
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header">
          Alertes stock
          <?php if($stockFaible+$stockRupture>0): ?>
          <span class="badge-alert"><?= $stockFaible+$stockRupture ?> alertes</span>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <?php if(empty($alertesStock)): ?>
          <p class="text-muted small">Aucune alerte.</p>
          <?php else: foreach($alertesStock as $a): ?>
          <div class="alert-stock-row <?= $a['niveau'] ?>">
            <span class="alert-stock-name"><?= htmlspecialchars($a['nom']) ?></span>
            <span class="alert-stock-qty"><?= $a['quantite'] ?> / <?= $a['stock_minimum'] ?></span>
          </div>
          <?php endforeach; endif; ?>
          <?php if($stockFaible+$stockRupture>0): ?>
          <a href="alertes.php" class="btn btn-sm btn-outline-secondary mt-2 w-100">Voir toutes les alertes</a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Dernières ventes -->
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header">Dernières ventes <a href="ventes.php" class="btn btn-sm btn-outline-secondary">Voir tout</a></div>
        <?php foreach($dernieres as $v): ?>
        <div class="vente-row">
          <div>
            <div class="vente-row-client"><?= htmlspecialchars($v['client_nom']?:'Client anonyme') ?></div>
            <div class="vente-row-date"><?= date('d/m/Y', strtotime($v['date_vente'])) ?></div>
          </div>
          <div style="text-align:right">
            <div class="vente-row-montant"><?= fcfa($v['montant_total']) ?></div>
            <span class="vente-row-badge badge-<?= $v['statut'] ?>"><?= $v['statut'] ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family='Inter,sans-serif';
const TT={backgroundColor:'#1e293b',titleColor:'#f8fafc',bodyColor:'#94a3b8',padding:12,cornerRadius:8,borderWidth:0};

const mois  = <?= json_encode(array_column($chart6m,'m')) ?>;
const nbV   = <?= json_encode(array_map('intval',array_column($chart6m,'nb'))) ?>;
const caArr = <?= json_encode(array_map('intval',array_column($chart6m,'ca'))) ?>;

new Chart(document.getElementById('ventesChart'),{
  type:'bar',
  data:{labels:mois,datasets:[
    {label:'Ventes',data:nbV,backgroundColor:'#1a2332',borderRadius:6,yAxisID:'y',barPercentage:.5,order:2},
    {label:'CA',data:caArr,type:'line',borderColor:'#F4A261',backgroundColor:'rgba(244,162,97,0.07)',fill:true,tension:.4,pointBackgroundColor:'#F4A261',pointRadius:5,pointBorderWidth:0,yAxisID:'y1',borderWidth:2,order:1}
  ]},
  options:{
    responsive:true,maintainAspectRatio:false,
    interaction:{mode:'index',intersect:false},
    plugins:{legend:{display:false},tooltip:{...TT,callbacks:{label:c=>c.datasetIndex===0?` ${c.raw} ventes`:` ${new Intl.NumberFormat('fr-FR').format(c.raw)} FCFA`}}},
    scales:{
      x:{grid:{display:false},ticks:{color:'#94a3b8',font:{size:11}},border:{display:false}},
      y:{grid:{color:'#f8fafc'},ticks:{color:'#94a3b8',font:{size:11}},border:{display:false}},
      y1:{position:'right',grid:{drawOnChartArea:false},ticks:{color:'#F4A261',font:{size:10},callback:v=>new Intl.NumberFormat('fr-FR',{notation:'compact'}).format(v)},border:{display:false}}
    }
  }
});

const catLabels = <?= json_encode(array_column($catData,'categorie')) ?>;
const catVals   = <?= json_encode(array_map('intval',array_column($catData,'total'))) ?>;
const catColors = ['#1a2332','#F4A261','#3b82f6','#16a34a','#8b5cf6','#94a3b8'];

new Chart(document.getElementById('catChart'),{
  type:'doughnut',
  data:{labels:catLabels,datasets:[{data:catVals,backgroundColor:catColors,borderWidth:0,hoverOffset:6}]},
  options:{responsive:true,maintainAspectRatio:false,cutout:'72%',plugins:{legend:{display:false},tooltip:{...TT}}}
});

const spkLabels = <?= json_encode(array_column($spark,'j')) ?>;
const spkData   = <?= json_encode(array_map('intval',array_column($spark,'ca'))) ?>;

new Chart(document.getElementById('sparkChart'),{
  type:'line',
  data:{labels:spkLabels,datasets:[{data:spkData,borderColor:'#3b82f6',backgroundColor:'rgba(59,130,246,0.07)',fill:true,tension:.4,pointRadius:2,pointBackgroundColor:'#3b82f6',borderWidth:1.5}]},
  options:{
    responsive:true,maintainAspectRatio:false,
    plugins:{legend:{display:false},tooltip:{...TT,callbacks:{label:c=>` ${new Intl.NumberFormat('fr-FR').format(c.raw)} FCFA`}}},
    scales:{x:{display:false},y:{display:false}}
  }
});
</script>
<script src="assets/script.js"></script>
</body>
</html>
