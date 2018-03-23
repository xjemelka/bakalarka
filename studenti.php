<?php 

require 'start.php';
require 'overeni.php';


/* @var $db PDO */
/* @tpl Latte\Engine */

//ověřuje jestli je učitel - později přesunout takové podmínky do souborů jako je overeni.php pro každé oprávnění zvlášť
if ($_SESSION["user"]["typ"] != 1){
    header('Location: index.php');
}
    
    if(!empty($_POST['login'])){
        try {
            $vstup = ($_POST['login']);
            if (strpos($vstup, ';') !== false) {
                $studenti = explode(";", $vstup);
            }
            else{
                $studenti = array();
                $studenti[0] = $vstup;
            }
            foreach ($studenti as $student){
                $hash = sha1('heslo');
                $uzivatel = $db->prepare('INSERT into nastaveni.uzivatele (login, heslo, email, typ) values (:log,:hes,:ema,:typ)');
                $uzivatel -> bindValue(":log",$student);
                $uzivatel -> bindValue(":hes",$hash);
                $uzivatel -> bindValue(":ema",$student.'@mendelu.cz');
                $uzivatel -> bindValue(":typ",2);
                $uzivatel -> execute();
            }
            header('Location: generuj_data.php');
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
    if(!empty($_POST['smaz_id_uzivatele'])){
         try {
             $smaz = $db->query("DROP DATABASE IF EXISTS ".$_POST['smaz_login_uzivatele']);
             $smaz = $db->query("DROP DATABASE IF EXISTS ".$_POST['smaz_login_uzivatele']."_otazky");
             $smaz = $db->prepare("DELETE FROM nastaveni.uzivatele WHERE id_uzivatele = :id");
             $smaz -> bindValue(":id",$_POST['smaz_id_uzivatele']);
             $smaz -> execute();
             header('Location: studenti.php');
         } catch (Exception $ex) {
            die($ex->getMessage());
         }
    }
    
        $studenti = $db->query("SELECT id_uzivatele, login, email, zadani, body, max_body,
                                CASE COALESCE(odpoved4, odpoved3, odpoved2, odpoved1)
                                    WHEN odpoved4 THEN 4
                                    WHEN odpoved3 THEN 3
                                    WHEN odpoved2 THEN 2
                                    WHEN odpoved1 THEN 1
                                    ELSE 0
                                END AS pocet_odpovedi 
                            from nastaveni.uzivatele where typ = 2");
    

    $tplVars["studenti"] = $studenti->fetchAll();
    
    $tplVars["titulek"] = "Přehled studentů";
    $tplVars["navigace"] = 2;
    $tpl->render("studenti.latte", $tplVars);
?>

