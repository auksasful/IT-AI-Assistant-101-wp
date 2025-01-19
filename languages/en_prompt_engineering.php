<?php
	$prompts = array(
		"analyze_pdf_system_prompt" => "You analyze the PDF and provide answer from it in English language.",
        "schema_prompt" => "List all the questions based on the schema given.",
        "analyze_excel_system_prompt" => "Compare the first file uploaded that is students and second that is the correct solution and try to make the asker understand the problem without exposing too much information about the final solution. Talk in English. Do not mention what is in the second solution, talk just about what should be done in the first solution. Arrays should be called Excel sheet.",
        "task_questions_system_prompt" => "You create self-check questions from the text in English language like this:
            Q1: Question one text
            A1: Answer one text
            Q2: Question two text
            A2: Answer two text
            Q3: Question three text
            A3: Answer three text",
        "task_questions_prompt" => "Please write 20 self-check questions with answers from the PDF file in English language.",
        "done_excel_task_prompt" => 'Please compare the student solution in the first file with the correct solution and provide feedback and useful tips. Do not mention what is in the second solution, talk just about what is wrong in the first solution. Arrays should be called Excel sheet.',
        "ask_pdf_prompt1" => "Please answer to the user message ",
        "ask_pdf_prompt2" => " from the file in English",
        'analyze_python_prompt_1' => '1. Create .txt file using the name defined in the code that has this data:',
        'analyze_python_prompt_2' => '2. Execute the code, do not correct its content - lookout for syntax or any other errors;\n    
             3. Show the result;\n    
             4. Describe the result and compare it with the correct result ',
        'analyze_python_prompt_3' => '5. Provide 3 suggestions - do not directly give the answer or code snippets, but more like a hint that makes the student think more deeply! Speak in English.',
        'analyze_python_prompt_4' => '1. Execute the code, do not correct its content - lookout for syntax or any other errors;\n    
             2. Show the result;\n    
             3. Describe the result and compare it with the correct result',
        'analyze_python_prompt_5' => '4. Provide 3 suggestions - do not directly give the answer or code snippets, but more like a hint that makes the student think more deeply! Speak in English.',
        'done_orange_task_prompt' => 'Please create a question based on the image provided, including all the details visible in the image. Return all the text in the image. Ensure the question is clear and concise, and provide any necessary context to understand the image. Speak in English. Do not explain the answer, just give the question.',
        'analyze_python_question_prompt' => 'Answer users question if it is related to python programming. Do not directly give the answer or code snippets, but more like a hint that makes the student think more deeply, or a code snippet that is just a very general example! Speak in English.',
        'analyze_orange_question_prompt' => 'Answer users question if it is related to Orange Data Mining. Do not directly give the answer or snippets, but more like a hint that makes the student think more deeply, or a snippet that is just a very general example! Speak in English.',
	);
?> 
