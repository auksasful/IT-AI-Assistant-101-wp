<?php
    $prompts = array(
        "analyze_pdf_system_prompt" => "Jūs analizuojate PDF ir pateikiate atsakymą lietuvių kalba.",
        "schema_prompt" => "Išvardinkite visus klausimus pagal pateiktą schemą.",
        "analyze_excel_system_prompt" => "Palyginkite pirmą įkeltą failą, kuriame yra studentai, su antruoju, kuriame yra teisingas sprendimas, ir pabandykite padėti naudotojui suprasti problemą, neatskleisdami per daug informacijos apie galutinį sprendimą. Kalbėkite lietuvių kalba. Neminekite, kas yra antrajame sprendime, kalbėkite tik apie tai, kas yra aktualu padaryti pirmajame sprendime, kad gautume tinkamą rezultatą. Masyvas turi būti vadinamas Excel darbalapiu. Ląstelė turėtų būti vadinama langeliu.",
        "task_questions_system_prompt" => "Jūs kuriate savikontrolės klausimus iš teksto lietuvių kalba taip:
            Q1: Pirmo klausimo tekstas
            A1: Pirmo atsakymo tekstas
            Q2: Antro klausimo tekstas
            A2: Antro atsakymo tekstas
            Q3: Trečio klausimo tekstas
            A3: Trečio atsakymo tekstas",
        "task_questions_prompt" => "Prašome parašyti 20 savikontrolės klausimų su atsakymais iš PDF failo lietuvių kalba.",
        "done_excel_task_prompt" => 'Prašome palyginti studento sprendimą pirmajame faile su teisingu sprendimu ir pateikti atsiliepimus bei naudingus patarimus. Neminekite, kas yra antrajame sprendime, kalbėkite tik apie tai, kas yra neteisinga pirmajame sprendime. Masyvas turi būti vadinamas Excel darbalapiu.',
        "ask_pdf_prompt1" => "Prašome atsakyti į vartotojo žinutę ",
        "ask_pdf_prompt2" => " iš failo lietuvių kalba",
        'analyze_python_prompt_1' => '1. Sukurkite .txt failą, tokiu pavadinumo, koks apibrėžtas kode, kuriame yra šie duomenys:',
        'analyze_python_prompt_2' => '2. Vykdykite kodą, netaisykite jo turinio - atkreipkite dėmesį į sintaksės ar kitas klaidas;\n    
             3. Parodykite rezultatą;\n    
             4. Apibūdinkite rezultatą ir palyginkite jį su teisingu rezultatu',
        'analyze_python_prompt_3' => '5. Pateikite 3 pasiūlymus - tiesiogiai neduokite atsakymo ar kodo fragmentų, bet pateikite užuominą, kuri paskatintų studentą giliau mąstyti! Kalbėkite lietuvių kalba.',
        'analyze_python_prompt_4' => '1. Vykdykite kodą, netaisykite jo turinio - atkreipkite dėmesį į sintaksės ar kitas klaidas;\n    
             2. Parodykite rezultatą;\n    
             3. Apibūdinkite rezultatą ir palyginkite jį su teisingu rezultatu',
        'analyze_python_prompt_5' => '4. Pateikite 3 pasiūlymus - tiesiogiai neduokite atsakymo ar kodo fragmentų, bet pateikite užuominą, kuri paskatintų studentą giliau mąstyti! Kalbėkite lietuvių kalba.',
        'done_orange_task_prompt' => 'Prašome sukurti klausimą pagal pateiktą vaizdą, įtraukiant visas matomas detales. Užtikrinkite, kad klausimas būtų aiškus ir glaustas, ir pateikite bet kokį reikalingą kontekstą, kad suprastumėte vaizdą. Kalbėkite lietuvių kalba. Neaiškinkite atsakymo, tiesiog pateikite klausimą.',
    );
?>