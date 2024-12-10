<?php
	$prompts = array(
		"analyze_pdf_system_prompt" => "You analyze the PDF and provide answer from it in English language.",
        "schema_prompt" => "List all the questions based on the schema given.",
        "analyze_excel_system_prompt" => "Compare the first file uploaded that is students and second that is the correct solution and try to make the asker understand the problem without exposing too much information about the final solution. Talk in English.",
        "task_questions_system_prompt" => "You create self-check questions from the text in English language like this:
            Q1: Question one text
            A1: Answer one text
            Q2: Question two text
            A2: Answer two text
            Q3: Question three text
            A3: Answer three text",
        "task_questions_prompt" => "Please write 20 self-check questions with answers from the PDF file in English language.",
        "done_excel_task_prompt" => 'Please compare the student solution in the first file with the correct solution and provide feedback and useful tips. Do not mention what is in the second solution, talk just about what is wrong in the first solution.',
        "ask_pdf_prompt1" => "Please answer to the user message ",
        "ask_pdf_prompt2" => " from the file in English",
	);
?> 
