<?php

require_once 'languageconfig.php';

/*
 * FAQ Page
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FAQ</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <style>
        body {
            background: #e0e0e0;
            padding: 15px 0;
        }
        .content {
            background: #fff;
            border-radius: 3px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.075), 0 2px 4px rgba(0, 0, 0, 0.0375);
            padding: 30px;
        }
        .panel-group {
            margin-bottom: 0;
        }
        .panel-group .panel {
            border-radius: 0;
            box-shadow: none;
        }
        .panel-group .panel .panel-heading {
            padding: 0;
        }
        .panel-group .panel .panel-heading h4 a {
            background: #f8f8f8;
            display: block;
            font-size: 12px;
            font-weight: bold;
            padding: 15px;
            text-decoration: none;
            transition: 0.15s all ease-in-out;
        }
        .panel-group .panel .panel-heading h4 a:hover,
        .panel-group .panel .panel-heading h4 a:not(.collapsed) {
            background: #fff;
            transition: 0.15s all ease-in-out;
        }
        .panel-group .panel .panel-heading h4 a:not(.collapsed) i:before {
            content: "\f068";
        }
        .panel-group .panel .panel-heading h4 a i {
            color: #999;
        }
        .panel-group .panel .panel-body {
            padding-top: 0;
        }
        .panel-heading + .panel-collapse > .list-group,
        .panel-heading + .panel-collapse > .panel-body {
            border-top: none;
        }
        .panel + .panel {
            border-top: none;
            margin-top: 0;
        }
        img {
            width: -webkit-fill-available;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content">
            <a href="<?php echo get_site_url(null, '/itaiassistant101/index'); ?>" class="btn btn-primary" style="margin-bottom: 20px;">
                <?php echo isset($lang['back_to_index']) ? $lang['back_to_index'] : 'Back to Index'; ?>
            </a>
            <div id="accordion" class="panel-group" role="tablist" aria-multiselectable="true">
                <div class="panel panel-default">
                    <div id="headingOne" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a role="button" data-toggle="collapse" data-parent="#accordion"
                                 href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                Kaip mokytojui gauti Gemini API raktą?
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseOne" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
                        <div class="panel-body">
                            <p>
                                1. Prisijunkite prie Google paskyros <br>
                                2. Eikite į <a href="https://aistudio.google.com/app/apikey" target="_blank">https://aistudio.google.com/app/apikey</a> <br>
                                3. Spauskite "Create API Key"<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq1_1.png'; ?>" alt="API Key"><br>
                                4. Paieškoje pasirinkite "Gemini API"<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq1_2.png'; ?>" alt="Gemini API project"><br>
                                5. Spauskite "Create API key in existing project"<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq1_3.png'; ?>" alt="Create API key in existing project"><br>
                                6. Jūsų API raktas sukurtas. Galite jį kopijuoti ir naudoti.<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq1_4.png'; ?>" alt="API key getting"><br>
                                7. Pakartotinai API raktą galite kopijuoti paspaudę ant jo sąraše.<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq1_5.png'; ?>" alt="API key copy"><br>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div id="headingTwo" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                                 href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                Ar Google Gemini API raktas yra nemokamas?
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
                        <div class="panel-body">
                            <p>
                                Taip, Google Gemini API raktas yra nemokamas ir pakankamas mokymosi tikslais. <br>
                                Jam naudoti nereikia nurodyti mokėjimo kortelės duomenų.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div id="headingThree" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                                 href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                Ar mano duomenys yra saugūs naudojant Google Gemini API?
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree">
                        <div class="panel-body">
                            <p>
                                Naudojant nemokamą Google Gemini API, pateikiama informacija gali<br>
                                 būti saugoma ir naudojama Google. Todėl rekomenduojama nepateikti konfidencialios informacijos.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div id="headingFour" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                            href="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                Kaip užsiregistruoti sistemoje, jei esu mokytojas?
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseFour" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingFour">
                        <div class="panel-body">
                            <p>
                                1. Atidarykite registracijos formą neprisijungę<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq4_1.png'; ?>" alt="Registration form"><br>
                                2. Užpildykte prisijungimo duomenis, <b>įskaitaint</b> ir API raktą ir spauskite "Registruotis"<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq4_2.png'; ?>" alt="Registration form"><br>
                                3. Jūsų paskyra sėkmingai sukurta. Esate automatiškai prijungti prie sistemos.<br>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div id="headingFive" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                            href="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                Kaip užsiregistruoti sistemoje, jei esu mokinys?
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseFive" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingFive">
                        <div class="panel-body">
                            <p>
                                1. Atidarykite registracijos formą neprisijungę<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq4_1.png'; ?>" alt="Registration form"><br>
                                2. Užpildykte prisijungimo duomenis, <b>neįvedant API rakto</b> ir spauskite "Registruotis"<br>
                                3. Jūsų paskyra sėkmingai sukurta.<br>
                                4. Įveskite mokytojo naudotojo vardą (paprastai vardo ir pavardės pirmos trys raidės ir skaičius, priklausomai nuo atitinkamų kombinacijų kiekio sistemoje, pradedant didžiąja raidė). Jei mokytojas vardu<br>
                                Mokytojas Mokytojas, tai naudotojo vardas gali būti MokMok arba MokMok2. Tuomet spauskite prisijungti.<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq5_1.png'; ?>" alt="Registration form"><br>
                                5. Palaukite, kol mokytojas Jus patvirtins. Tuomet turėsite galimybę prisijungti prie sistemos.<br>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div id="headingSix" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                            href="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                                Kaip mokytojas gali priimti mokinį į klasę?
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseSix" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingSix">
                        <div class="panel-body">
                            <p>
                                <i>Pastaba: norint pridėti naujai registruotą mokinį į klasę, jis turi užsiregistruoti sistemoje ir pateikti prisijungimo užklausą</i><br>
                                1. Prisijungę prie sistemos spauskite "Pasirinkti klasę"<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq6_1.png'; ?>" alt="Select class"><br>
                                2. Spauskite ant klasės Jūsų vardu.<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq6_2.png'; ?>" alt="Select class"><br>
                                3. Atidaromas mokinių sąrašas. Mokinių sąraše prie naujų mokinių turėtų būti mygtukas "Priimti į klasę". Spauskite jį.<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq6_3.png'; ?>" alt="Accept student"><br>
                                4. Iššokančiame lange spauskite "Patvirtinti"<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq6_4.png'; ?>" alt="Accept student"><br>
                                5. Mokinys sėkmingai priimtas į klasę.<br>
                                6. Norėdami pridėti mokinius į kitą klasę, tai galite padaryti klasių sąraše pasirinkę <b>ne vardinę</b> klasę ir paspaudę "Pridėti mokinius".<br>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div id="headingSeven" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                            href="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">
                                Kaip atkurti slaptažodį?
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseSeven" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingSeven">
                        <div class="panel-body">
                            <p>
                                1. Jei esate <b>mokytojas</b>, savo slaptažodį galite atkurti prisijungimo lange spausdami "Pamiršote slaptažodį?" ir įvedę savo Gemini API raktą<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq7_1.png'; ?>" alt="Forgot password"><br>
                                2. Jei esate <b>mokinys</b>, mokytojas gali atkurti Jūsų slaptažodį mokinių sąraše, paspaudęs mygtuką "atkurti slaptažodį". Tuomet Jūs turite įvesti mokytojui matomą laikiną slaptažodį kartu su savo prisijungimo vardu prisijungimo formoje. Prisijungę turėsite pasikeisti slaptažodį.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div id="headingEight" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                            href="#collapseEight" aria-expanded="false" aria-controls="collapseEight">
                                Kaip pakeisti API raktą?
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseEight" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingEight">
                        <div class="panel-body">
                            <p>
                                Jei esate mokytojas, API raktą galite pakeisti nustatymų srityje.<br>
                                1. Pokalbio lange spauskite mygtuką "Nustatymai"<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq8_1.png'; ?>" alt="Settings"><br>
                                2. Nustatymų lange spauskite "Pakeisti API raktą"<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq8_2.png'; ?>" alt="Change API key"><br>
                                3. Įveskite naują API raktą ir spauskite "Patvirtinti"<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq8_3.png'; ?>" alt="Change API key"><br>
                                4. Patvirtinkite veiksmą dar kartą<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq8_4.png'; ?>" alt="Change API key"><br>
                                5. Jei API raktas teisingas ir nėra naudojamas kito mokytojo, jis turėtų būti sėkmingai pakeistas.<br>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div id="headingNine" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                            href="#collapseNine" aria-expanded="false" aria-controls="collapseNine">
                                Kaip pakeisti slaptažodį?
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseNine" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingNine">
                        <div class="panel-body">
                            <p>
                                1. Prisijungę prie klasės spauskite "Nustatymai"<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq8_1.png'; ?>" alt="Settings"><br>
                                2. Nustatymų lange spauskite "Pakeisti slaptažodį"<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq9_1.png'; ?>" alt="Change password"><br>
                                3. Įveskite seną slaptažodį ir spauskite "Patvirtinti"<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq9_2.png'; ?>" alt="Change password"><br>
                                4. Įveskite naują slaptažodį ir spauskite "Patvirtinti"<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq9_3.png'; ?>" alt="Change password"><br>
                                5. Jei duomenys tinkami, Jūsų slaptažodis bus sėkmingai pakeistas.<br>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div id="headingTen" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                            href="#collapseTen" aria-expanded="false" aria-controls="collapseTen">
                                Kaip importuoti ar eksportuoti užduotis sistemoje?
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseTen" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTen">
                        <div class="panel-body">
                            <p>
                                Jei esate mokytojas, galite sukurti užduotis ir jas importuoti ar eksportuoti skirtingoms klasėms.<br>
                                1. Prisijungę prie sistemos spauskite "Nustatymai"<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq8_1.png'; ?>" alt="Settings"><br>
                                2. Nustatymų lange spauskite "Importuoti užduotis" arba "Eksportuoti užduotis"<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq10_1.png'; ?>" alt="Import or export tasks"><br>
                                3. Eksporto funkcija sukurs .zip failą su visomis užduotimis esamoje klasėje atsisiuntimui.<br>
                                4. Importo funkcija leis įkelti .zip failą su užduotimis į esamą klasę, kuris anksčiau buvo išeksporuotas iš šios sistemos. Klasė pasipildys naujomis užduotimis.<br>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div id="headingEleven" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                            href="#collapseEleven" aria-expanded="false" aria-controls="collapseEleven">
                                Ką reiškia pasirinkimas "Naudoti dokumento informaciją" pokalbio lange?
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseEleven" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingEleven">
                        <div class="panel-body">
                            <p>
                                Šis pasirinkimas nurodo, ar pokalbyje naudojami papildomi šaltiniai, pvz.<br>
                                •	PDF tipo užduotyje atsakymams į klausimus naudojamas mokytojo įkeltas PDF dokumentas;<br>
                                •	Excel skaičiuoklės užduoties atsakymai remiasi šio įrankio pamokomis GeeksforGeeks ir W3Schools platformose.<br>
                                •	Python užduoties atsakymai remiasi šios programavimo kalbos naudotojų vadovu, taip pat atskirų bibliotekų, aktualių duomenų tyrybai dokumentacijomis: numpy, sklearn, matplotlib, pandas.<br>
                                •	Orange Data Mining paremta oficialia dokumentacija ir dar vienu puslapiu gilyn. Dažnai tai būna aktualus straipsnis Wikipedia svetainėje.<br>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div id="headingTwelve" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                            href="#collapseTwelve" aria-expanded="false" aria-controls="collapseTwelve">
                                Kokių tipų yra užduotys?
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseTwelve" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwelve">
                        <div class="panel-body">
                            <p>
                            Sistema palaiko keturis užduočių tipus, susijusius su duomenų tyryba pagal informatikos mokymo programą:<br>
                            •	PDF teorinė mokymosi medžiaga su savitikros klausimais;<br>
                            •	Excel skaičiuoklės užduotis su galimybe mokiniui lyginti savo sprendimą su teisingu atsakymu;<br>
                            •	Python programavimo užduotis. Priemonė naudoja Google integruotą kompiliatorių patikrinimui ir atsakymo pateikimui.<br>
                            •	Orange Data Mining duomenų tyrybos užduotis. Galimybė klausti įkeliant paveikslėlį.<br>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div id="headingT13" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                            href="#collapseT13" aria-expanded="false" aria-controls="collapseT13">
                                Kaip sukurti PDF tipo užduotį?
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseT13" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingT13">
                        <div class="panel-body">
                            <p>
                                Jei esate mokytojas, galite sukurti užduotis. Norėdami sukurti PDF tipo užduotį, turite atlikti šiuos veiksmus:<br>
                                1. Prisijungę prie sistemos turite paspausti "Pridėti užduotį"<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq13_1.png'; ?>" alt="Add task"><br>
                                2. Įveskite užduoties pavadinimą, aprašą, pasirinkite tipą "PDF" ir įkelkite PDF failą mokymuisi.<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq13_2.png'; ?>" alt="Add task"><br>
                                3. Pasitikrinkite informaciją ir spauskite "Kitas"<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq13_3.png'; ?>" alt="Add task"><br>
                                4. Esant poreikiui galite įvesti užduoties santrauką bei savitikros klausimus. Tam galite naudoti DI funkcijas, taip pat redaguoti DI sugeneruotą informaciją. <br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq13_4.png'; ?>" alt="Add task"><br>
                                5. Pasitikrinkite informaciją ir spauskite "Išsaugoti užduotį"<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq13_5.png'; ?>" alt="Add task"><br>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div id="headingT131" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                            href="#collapseT131" aria-expanded="false" aria-controls="collapseT131">
                                Kaip naudoti PDF tipo užduotį pokalbių robote?
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseT131" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingT13">
                        <div class="panel-body">
                            <p>
                                1. Prisijungę prie sistemos turite pasirinkti norimą PDF tipo užduotį sąraše kairėje pusėje.<br>
                                2. Pokalbio lange galite įvesti klausimus ir gauti atsakymus iš dokumento, paspaudę mygtuką "Siųsti" arba klaviatūros klavišą "Enter".<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq14_1.png'; ?>" alt="Chat with PDF"><br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq14_3.png'; ?>" alt="Chat with PDF"><br>
                                3. Pokalbio lango viršuje galite matyti užduoties pavadinimą, aprašą, nuorodą į failą, santrauką ir išskleidžiamus savitikos klausimus.<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq14_2.png'; ?>" alt="Add task"><br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq14_4.png'; ?>" alt="Add task"><br>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div id="headingT141" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                            href="#collapseT141" aria-expanded="false" aria-controls="collapseT141">
                            Kaip sukurti Excel tipo užduotį?
                            <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseT141" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingT141">
                        <div class="panel-body">
                            <p>
                                Jei esate mokytojas, galite sukurti užduotis. Norėdami sukurti Excel tipo užduotį, turite atlikti šiuos veiksmus:<br>
                                1. Prisijungę prie sistemos turite paspausti "Pridėti užduotį"<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq13_1.png'; ?>" alt="Add task"><br>
                                2. Įveskite užduoties pavadinimą, aprašą, pasirinkite tipą "PDF" ir įkelkite PDF failą mokymuisi.<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq15_1.png'; ?>" alt="Add task"><br>
                                3. Pasitikrinkite informaciją ir spauskite "Išsaugoti užduotį"<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq15_2.png'; ?>" alt="Add task"><br>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div id="headingT14" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                            href="#collapseT14" aria-expanded="false" aria-controls="collapseT14">
                            Kaip naudoti Excel tipo užduotį pokalbių robote?
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseT14" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingT14">
                        <div class="panel-body">
                            <p>
                            1. Prisijungę prie sistemos turite pasirinkti norimą Excel tipo užduotį sąraše kairėje pusėje.<br>
                            2. Pokalbio lange galite įvesti klausimus ir gauti atsakymus iš interneto šaltinių, paspaudę mygtuką "Siųsti" arba klaviatūros klavišą "Enter".<br>
                            <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq16_1.png'; ?>" alt="Chat with Excel"><br>
                            3. Apačioje atsakymo matomos nuorodos, surikiuotos pagal kosinusinį panašumą mažėjimo tvarka. Pagal jas pateiktas atsakymas.<br>
                            <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq16_2.png'; ?>" alt="Chat with Excel"><br>
                            4. Pokalbio lango viršuje galite matyti užduoties pavadinimą, aprašą ir nuorodą į failą.<br>
                            <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq16_3.png'; ?>" alt="Add task"><br>
                            5. Pokalbio lange paspaudę mygtuką "Browse", galite įkelti savo sprendimą ir gauti palyginimą su teisingu sprendimu.<br>
                            <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq16_4.png'; ?>" alt="Add task"><br>
                            </p>
                        </div>
                    </div>
                </div>


                <div class="panel panel-default">
                    <div id="headingT15" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                            href="#collapseT15" aria-expanded="false" aria-controls="collapseT15">
                            Kaip sukurti Python tipo užduotį?
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseT15" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingT15">
                        <div class="panel-body">
                            <p>
                                Jei esate mokytojas, galite sukurti užduotis. Norėdami sukurti Python tipo užduotį, turite atlikti šiuos veiksmus:<br>
                                1. Prisijungę prie sistemos turite paspausti "Pridėti užduotį"<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq13_1.png'; ?>" alt="Add task"><br>
                                2. Įveskite užduoties pavadinimą, aprašą, pasirinkite tipą "Python" ir galite įkelti užduoties PDF failą bei duomenų failą.<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq17_1.png'; ?>" alt="Add task"><br>
                                3. Pasitikrinkite informaciją ir spauskite "Išsaugoti užduotį"<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq17_2.png'; ?>" alt="Add task"><br>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div id="headingT151" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                            href="#collapseT151" aria-expanded="false" aria-controls="collapseT151">
                            Kaip naudoti Python tipo užduotį pokalbių robote?
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseT151" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingT151">
                        <div class="panel-body">
                            <p>
                            1. Prisijungę prie sistemos turite pasirinkti norimą Python tipo užduotį sąraše kairėje pusėje.<br>
                            2. Pokalbio lange galite įvesti klausimus ir gauti atsakymus iš interneto šaltinių, paspaudę mygtuką "Siųsti" arba klaviatūros klavišą "Enter".<br>
                            <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq18_1.png'; ?>" alt="Chat with Excel"><br>
                            3. Apačioje atsakymo matomos nuorodos, surikiuotos pagal kosinusinį panašumą mažėjimo tvarka. Pagal jas pateiktas atsakymas.<br>
                            4. Pokalbio lango viršuje galite matyti užduoties pavadinimą, aprašą ir nuorodą į failą.<br>
                            5. Pokalbio lange paspaudę mygtuką "Browse", galite įkelti savo sprendimą ir gauti kompiliavimo rezultatą ir naudingus patarimus.<br>
                            <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq18_2.png'; ?>" alt="Add task"><br>
                            <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq18_3.png'; ?>" alt="Add task"><br>

                            </p>
                        </div>
                    </div>
                </div>


                <div class="panel panel-default">
                    <div id="headingT16" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                            href="#collapseT16" aria-expanded="false" aria-controls="collapseT16">
                            Kaip sukurti Orange tipo užduotį?
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseT16" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingT16">
                        <div class="panel-body">
                            <p>
                                Jei esate mokytojas, galite sukurti užduotis. Norėdami sukurti Orange tipo užduotį, turite atlikti šiuos veiksmus:<br>
                                1. Prisijungę prie sistemos turite paspausti "Pridėti užduotį"<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq13_1.png'; ?>" alt="Add task"><br>
                                2. Įveskite užduoties pavadinimą, aprašą, pasirinkite tipą "Orange" ir galite įkelti užduoties PDF failą bei duomenų failą.<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq19_1.png'; ?>" alt="Add task"><br>
                                3. Pasitikrinkite informaciją ir spauskite "Išsaugoti užduotį"<br>
                                <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq19_2.png'; ?>" alt="Add task"><br>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div id="headingT161" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                            href="#collapseT161" aria-expanded="false" aria-controls="collapseT161">
                            Kaip naudoti Orange tipo užduotį pokalbių robote?
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseT161" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingT161">
                        <div class="panel-body">
                            <p>
                            1. Prisijungę prie sistemos turite pasirinkti norimą Orange tipo užduotį sąraše kairėje pusėje.<br>
                            2. Pokalbio lange galite įvesti klausimus ir gauti atsakymus iš interneto šaltinių, paspaudę mygtuką "Siųsti" arba klaviatūros klavišą "Enter".<br>
                            <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq20_1.png'; ?>" alt="Chat with Orange"><br>
                            3. Apačioje atsakymo matomos nuorodos, surikiuotos pagal kosinusinį panašumą mažėjimo tvarka. Pagal jas pateiktas atsakymas.<br>
                            4. Pokalbio lango viršuje galite matyti užduoties pavadinimą, aprašą ir nuorodą į failą.<br>
                            5. Pokalbio lange paspaudę mygtuką "Browse", galite įkelti savo sprendimo ekrano nuotrauką ir gauti naudingus patarimus.<br>
                            <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq20_2.png'; ?>" alt="Add task"><br>
                            <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq20_3.png'; ?>" alt="Add task"><br>

                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div id="headingT17" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                            href="#collapseT17" aria-expanded="false" aria-controls="collapseT17">
                            Kaip redaguoti ar panaikinti užduotį?
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseT17" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingT17">
                        <div class="panel-body">
                            <p>
                            Jei esate mokytojas, galite redaguoti ar panaikinti užduotis. Norėdami tai padaryti, turite atlikti šiuos veiksmus:<br>
                            1. Prisijungę prie sistemos turite paspausti tris taškus prie norimos redaguoti ar naikinti užduoties sąraše kairėje pusėje.<br>
                            <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq21_1.png'; ?>" alt="Edit or delete task"><br>
                            2. Atsiranda langas, kuriame galite pasirinkti norimą veiksmą.<br>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div id="headingT18" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                            href="#collapseT18" aria-expanded="false" aria-controls="collapseT18">
                            Kaip išvalyti užduoties pokalbio istoriją?
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseT18" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingT18">
                        <div class="panel-body">
                            <p>
                            1. Prisijungę prie sistemos turite paspausti mygtuką "Išvalyti pokalbių istoriją" pokalbio lange.<br>
                            <img src="<?php echo plugin_dir_url(__FILE__) . 'default_student_tasks/faq22_1.png'; ?>" alt="Clear chat history"><br>
                            2. Atsiranda langas, kuriame turite patvirtinti norimą veiksmą.<br>
                            3. Patvirtinus veiksmą, pokalbio istorija bus išvalyta.<br>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div id="headingT19" class="panel-heading" role="tab">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                            href="#collapseT19" aria-expanded="false" aria-controls="collapseT18">
                            Kas yra kosinusinis panašumas?
                                <i class="pull-right fa fa-plus"></i>
                            </a>
                        </h4>
                    </div>
                    <div id="collapseT19" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingT19">
                        <div class="panel-body">
                            <p>
                            Kosinusinis panašumas yra matematinis metodas, skirtas nustatyti, kaip du vektoriai yra panašūs vienas į kitą. Tai yra vienas iš dažniausiai naudojamų metodų teksto analizėje, kai reikia nustatyti, kiek du tekstiniai fragmentai yra panašūs vienas kitam.<br>
                            Dažnai taikoma didžiosios kalbos modeliams, kai reikia nustatyti, kiek du tekstiniai fragmentai yra panašūs viena į kitą.<br>
                            Plačiau: <a href="https://medium.com/@arjunprakash027/understanding-cosine-similarity-a-key-concept-in-data-science-72a0fcc57599">Medium straipsnis</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>
</html>
</html>