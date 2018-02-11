<?php 

require 'start.php';
require 'overeni.php';


/* @var $db PDO */
/* @tpl Latte\Engine */

//ověřuje jestli je učitel - později přesunout takové podmínky do souborů jako je overeni.php pro každé oprávnění zvlášť
if ($_SESSION["user"]["type"] != 1 or (empty($_POST['db']) and empty($_SESSION['db']))){
    header('Location: index.php');
}
    
    if(!empty($_POST['db'])){
        //ochrana aby nebyla vybrána neexistující/špatná db
        try {
            $stmt = $db->prepare("SELECT schema_name AS nazev
            FROM information_schema.SCHEMATA
            WHERE schema_name = :db
            and schema_name NOT IN ('nastaveni', 'information_schema', 'mysql', 'performance_schema', 'phpmyadmin')");
            $stmt->bindvalue(":db", $_POST['db']);
            $stmt->execute();

            $db = $stmt->fetch();
            if($db){
                $_SESSION['db'] = $_POST['db'];
                header('Location: tabulky.php');
                exit;
            }else{
                $tplVars['hlaska'] = "Databáze nenalezena.";
            } 
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
    $dotaz_tabulky = "SELECT tab.table_name as nazev, exp.id_formaty, exp.id_zpusoby
    FROM information_schema.tables tab
    LEFT JOIN nastaveni.export exp
    ON tab.table_schema = exp.databaze AND tab.table_name = exp.tabulka
    WHERE tab.table_schema= :db
    AND tab.table_type='BASE TABLE'";
    
    $tabulky = $db->prepare($dotaz_tabulky);
    $tabulky->bindvalue(":db", $_SESSION['db']);
    $tabulky->execute();
   
    $format = $db->prepare("select id_formaty, nazev FROM nastaveni.formaty");
    $format->execute();
    $zpusob = $db->prepare("select id_zpusoby, nazev FROM nastaveni.zpusoby");
    $zpusob->execute();
    
    if(!empty($_POST)){
        try {
            $tabulky2 = $db->prepare($dotaz_tabulky);
            $tabulky2->bindvalue(":db", $_SESSION['db']);
            $tabulky2->execute();
            $tabulky2 = $tabulky2->fetchAll();
            $chyba = 0;
            foreach ($tabulky2 as $tabulka) {
                //kontrola vyplnění údajů
                if (empty($_POST[$tabulka['nazev']."_format"]) || empty($_POST[$tabulka['nazev']."_zpusob"]) ){
                    $chyba = 1;
                }
            }
            if($chyba==1){
                $tplVars['hlaska'] = "Nebylo zadáno vše";
                $_POST['db'] = $_SESSION['db'];
                $tplVars["form"] = $_POST;
            }
            else{
                //update nebo insert nastavení, vstup je platný
                $tabulky3 = $db->prepare($dotaz_tabulky);
                $tabulky3->bindvalue(":db", $_SESSION['db']);
                $tabulky3->execute();
                $tabulky3 = $tabulky3->fetchAll();
                foreach ($tabulky2 as $tabulka) {
                    if (empty($tabulka['id_formaty'])){
                        $insert = $db->prepare("insert into nastaveni.export (databaze,tabulka,id_formaty,id_zpusoby) values (:db, :tb, :fo, :zp)");
                        $insert->bindValue(":db", $_SESSION['db']);
                        $insert->bindValue(":tb", $tabulka['nazev']);
                        $insert->bindValue(":fo", $_POST[$tabulka['nazev']."_format"]);
                        $insert->bindValue(":zp", $_POST[$tabulka['nazev']."_zpusob"]);
                        $insert->execute();
                    }
                    else{
                        $update = $db->prepare("update nastaveni.export set id_formaty=:fo,id_zpusoby=:zp where databaze=:db and tabulka=:tb");
                        $update->bindValue(":db", $_SESSION['db']);
                        $update->bindValue(":tb", $tabulka['nazev']);
                        $update->bindValue(":fo", $_POST[$tabulka['nazev']."_format"]);
                        $update->bindValue(":zp", $_POST[$tabulka['nazev']."_zpusob"]);
                        $update->execute();
                    }
                }
                $tplVars['hlaska'] = "Nastavení úspěšně aktualizováno";
                $_POST['db'] = $_SESSION['db'];
                $tplVars["form"] = $_POST;
            }
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    $tplVars["tabulky"] = $tabulky->fetchAll();
    $tplVars["formaty"] = $format->fetchAll();
    $tplVars["zpusoby"] = $zpusob->fetchAll();
    
    $tplVars["titulek"] = "Nastavení tabulek";
    $tplVars["navigace"] = 1;
    $tpl->render("tabulky.latte", $tplVars);
?>
