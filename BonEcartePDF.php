<?php
require_once('TCPDF/tcpdf_import.php');
require 'php/database.inc';
require 'php/StockVehicule.inc';
require 'php/Produit.inc';
require "php/CommandeDetail.inc";
require 'php/Statistique.inc';
$database=new database();
require 'php/VerifierUser.php';
if(isset($_GET["idVendeur"])){
  $result=$database->query("select vendeur.nom as nomvendeur,vendeur.prenom as prenom,vehicule.nom as nomvehicule from vendeur join vehicule
  on vendeur.id_vehicule=vehicule.id_vehicule and vendeur.id_vendeur=".$_GET["idVendeur"]);
  $Vendeur=mysqli_fetch_assoc($result);
   $where="";
  if(!empty($_GET["depart"])&&!empty($_GET["jusque"])){
   $where="and premier_bon.date BETWEEN '".$_GET["depart"]."' and '".$_GET["jusque"]."'";
  }
  $result=$database->query("select journee.id_journee as idjournee,sum(qte_vendue_dd*dd.prix+qte_vendue_dg*dg.prix+qte_vendue_sg*sg.prix) as facture ,recette
 ,recette-sum(qte_vendue_dd*dd.prix+qte_vendue_dg*dg.prix+qte_vendue_sg*sg.prix) as ecart,DATE_FORMAT(journee.date,'%d/%m/%Y') as date from commande_detail
 join produit_prix as dd on commande_detail.id_produit_prix_dd=dd.id_produit_prix
 join produit_prix as dg on commande_detail.id_produit_prix_dg=dg.id_produit_prix
 join produit_prix as sg on commande_detail.id_produit_prix_sg=sg.id_produit_prix
 join journee on commande_detail.id_journee=journee.id_journee
 where id_vendeur=".$_GET["idVendeur"]."  group by commande_detail.id_journee order by journee.date ASC");
  $bon=array();
  while ($row=mysqli_fetch_assoc($result)) {
     $bon[]=new Statistique($row["idjournee"],$row["date"],$row["facture"],$row["recette"],$row['ecart']);
  }
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, "A4", true, 'UTF-8', false);

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 002');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

$pdf->SetMargins("15", "0", PDF_MARGIN_RIGHT);

$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    require_once(dirname(__FILE__).'/lang/eng.php');
    $pdf->setLanguageArray($l);
}
$pdf->SetFont('times', 'BI', 12);

$pdf->AddPage();

$pdf->SetFont('times', 'BI', 20);

$pdf->SetY(5,true,false);
$pdf->SetX(90,false);
$pdf->writeHTML("<label>Bon Ecart</label>", true, false, true, false, '');

$pdf->SetFont('times', 'BI', 12);

$pdf->SetY(18,true,false);
$pdf->SetX(10,false);
$pdf->writeHTML("<label>Vehicule : ".$Vendeur["nomvehicule"]."</label>", true, false, true, false, '');

$pdf->SetY(18,true,false);
$pdf->SetX(60,false);
$pdf->writeHTML("<label>Vendeur : ".$Vendeur["nomvendeur"]." ".$Vendeur["prenom"]."</label>", true, false, true, false, '');

$pdf->SetY(18,true,false);
$pdf->SetX(135,false);
$pdf->writeHTML("<label> Du ".date_format(date_create($_GET["depart"]),"d/m/Y")." Au "
                 .date_format(date_create($_GET["jusque"]),"d/m/Y")." </label>", true, false, true, false, '');

$facture=0;
$recette=0;
$ecart=0;

$txt="<table border=\"1\" cellpadding=\"2\" cellspacing=\"0\" align=\"center\">";

$txt.="<tr   nobr=\"true\"><th width=\"150\">Date</th><th>Facture</th><th>Recette</th><th>Ecart</th></tr>";
foreach ($bon as $key => $value) {
  $facture+=$value->facture;
  $recette+=$value->recette;
  $ecart+=$value->ecart;
  $txt.="<tr  nobr=\"true\"><td >".$value->date."</td><td>".$value->facture."</td><td>$value->recette</td>
  <td>".$value->ecart."</td></tr>";
}
$txt.="<tr><td>Total</td><td>$facture</td><td>$recette</td><td>$ecart</td></tr>";
$txt.="</table>";

$pdf->SetY(30,true,false);
$pdf->SetX(5,false);

$pdf->writeHTML($txt, true, false, true, false, '');

$pdf->Output('BonEcarte.pdf', 'I');
}
?>
