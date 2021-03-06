<?php 

require 'start.php';
require 'overeni.php';


/* @var $db PDO */
/* @tpl Latte\Engine */

//ověřuje jestli je učitel - později přesunout takové podmínky do souborů jako je overeni.php pro každé oprávnění zvlášť
if ($_SESSION["user"]["typ"] != 1 or ((empty($_SESSION['db']) or empty($_SESSION['kategorie'])) and (empty($_GET['zadani']) or empty($_GET['kategorie'])))){
    header('Location: index.php');
}
    //if (empty($_SESSION['db']) || empty($_SESSION['kategorie'])){
        //$_SESSION['db']=$_GET['zadani'];
        //$_SESSION['kategorie']=$_GET['kategorie'];
    //}
        
        if (!empty($_GET['kategorie'])){
            $_SESSION['kategorie']=$_GET['kategorie'];
        }
    
    //$db->query("USE ".$_SESSION['db']."_otazky");
    
    if ( !empty($_POST['nazev']) ) {
        try {
            $kategorie = $db->prepare('UPDATE kategorie set nazev=:naz,otazek=:ota,body=:bod where id_kategorie=:kat');
            $kategorie -> bindValue(":naz",$_POST['nazev']);
            $kategorie -> bindValue(":ota",$_POST['otazek']);
            $kategorie -> bindValue(":bod",$_POST['body']);
            $kategorie -> bindValue(":kat",$_SESSION['kategorie']);
            $kategorie -> execute();
            header('Location: '.$_SERVER['PHP_SELF']);
        } catch (Exception $e) {
            die($e->getMessage());
        }
        
    }
    
    if ( !empty($_POST['update']) ) {
        try {
            $otazky = $db->prepare("SELECT id_dotazy from dotazy where id_kategorie = :kat");
            $otazky -> bindValue(":kat",$_SESSION['kategorie']);    
            $otazky -> execute();
            $otazky = $otazky ->fetchAll();
            
            foreach ($otazky as $otazka){
                $text = ($_POST['text'.$otazka['id_dotazy']]);
                $sql = ($_POST['sql'.$otazka['id_dotazy']]);
                if (strlen(trim($text))>0 && strlen(trim($sql))>0){
                    $dotaz = $db->prepare('UPDATE dotazy set text=:text,s_q_l=:sql where id_dotazy = :dot');
                    $dotaz -> bindValue(":text",$text);
                    $dotaz -> bindValue(":sql",$sql);
                    $dotaz -> bindValue(":dot",$otazka['id_dotazy']);
                    $dotaz -> execute();
                }
                else{
                    $smaz = $db->prepare("DELETE FROM dotazy WHERE id_dotazy = :id");
                    $smaz -> bindValue(":id",$otazka['id_dotazy']);
                    $smaz -> execute();
                }
            }            
            header('Location: '.$_SERVER['PHP_SELF']);
        } catch (Exception $e) {
            die($e->getMessage());
        }
        
    }
    
    if(!empty($_POST['text']) && !empty($_POST['sql'])){
        try {
            $text = ($_POST['text']);
            $sql = ($_POST['sql']);
            $dotaz = $db->prepare('INSERT into dotazy (text,s_q_l,id_kategorie) values (:text,:sql,:kat)');
            $dotaz -> bindValue(":text",$_POST['text']);
            $dotaz -> bindValue(":sql",$_POST['sql']);
            $dotaz -> bindValue(":kat",$_SESSION['kategorie']);
            $dotaz -> execute();
            header('Location: '.$_SERVER['PHP_SELF']);
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
    
        $otazky = $db->prepare("SELECT id_dotazy, text, s_q_l from dotazy where id_kategorie = :kat");
        $otazky -> bindValue(":kat",$_SESSION['kategorie']);    
        $otazky -> execute();
        
        $kategorie = $db->prepare("SELECT nazev, otazek, body from kategorie where id_kategorie = :kat");
        $kategorie -> bindValue(":kat",$_SESSION['kategorie']);    
        $kategorie -> execute();
        
    $tplVars["otazky"] = $otazky->fetchAll();
    $tplVars["kategorie"] = $kategorie->fetch();
    
    $tplVars["titulek"] = "Nastavení otázek zadání";
    $tplVars["navigace"] = 1;
    $tpl->render("otazky.latte", $tplVars);
?>

