<?php

class TaskData {
    private $systemPrompt = '';
    private $prompts = [];
    private $taskId;
    private $taskName;
    private $taskFileClean;
    private $taskFileCorrect;

    public function __construct($taskId = '', $taskName = '', $taskFileClean = '', $taskFileCorrect = '', $prompts = []) {
        $this->taskId = $taskId;
        $this->taskName = $taskName;
        $this->taskFileClean = $taskFileClean;
        $this->taskFileCorrect = $taskFileCorrect;
        $this->prompts = $prompts;
    }

    public function getPrompts() {
        return $this->prompts;
    }

    public function getTaskId() {
        return $this->taskId;
    }

    public function getTaskName() {
        return $this->taskName;
    }

    public function getTaskFileClean() {
        return $this->taskFileClean;
    }

    public function getTaskFileCorrect() {
        return $this->taskFileCorrect;
    }

    public function setPrompts($prompts) {
        $this->prompts = $prompts;
    }

    public function setSystemPrompt($systemPrompt) {
        $this->systemPrompt = $systemPrompt;
    }

    public function getSystemPrompt() {
        return $this->systemPrompt;
    }

    public function setTaskId($taskId) {
        $this->taskId = $taskId;
    }

    public function setTaskName($taskName) {
        $this->taskName = $taskName;
    }

    public function setTaskFileClean($taskFileClean) {
        $this->taskFileClean = $taskFileClean;
    }

    public function setTaskFileCorrect($taskFileCorrect) {
        $this->taskFileCorrect = $taskFileCorrect;
    }

    public function sampleTaskData() {
        $taskCleanFile = WP_CONTENT_URL . '/ITAIAssistant101/default_student_tasks/task1_clean.xlsx';
        $taskCorrectFile = WP_CONTENT_URL . '/ITAIAssistant101/default_student_tasks/task1_correct.xlsx';
        $taskText = 'You got an excel file. There is a task you have to do: Get the lenght for each line "a" be based on line "b" and overall area of the rectangle. Use formula for that.';
        $prompts = [$taskText, '', 'Congratulations! You have completed the task successfully!'];
        $taskData = new TaskData( 1, 'Task 1', $taskCleanFile, $taskCorrectFile, $prompts);
        // $taskData->setPrompts( [$taskText, '', 'Congratulations! You have completed the task successfully!']);
        $taskData->setSystemPrompt('You are a helpful and patient AI assistant designed to help students learn data analysis using Excel. You will guide students through exercises by:
- Providing them with a data analysis task in the form of an Excel file.
- Comparing their uploaded completed work to the correct result.
- Offering assistance to students struggling with the task, in a way presented.

Providing the Task
You will present the task to the student in a clear and concise way. For example:
"Hello! Today we will be working on calculating the area of a rectangle. Please download the Excel file provided, complete the calculations, and upload your finished file."
Comparing and Assessing Results
After the student uploads their completed Excel file, you will:
1. Compare this representation of the students work to the correct answer.
2. If the students answer is correct, provide positive feedback and move on to the next task or end the session.
Offering Assistance
3. If the students answer is incorrect, you will offer assistance. This can take many forms::
- Providing hints or clues: Suggesting the student review a specific concept or formula.
- Breaking down the task: Divide the problem into smaller, easier-to-manage steps.
- Providing examples: Show the student similar solved problems to illustrate the process.
- Offering encouraging feedback: Focus on the students effort and progress, even if they havent reached the correct solution.
- Tailoring your help based on the students specific mistakes: If a student repeatedly makes the same error, focus your guidance on that area.
- DO NOT SHOW THE CORRECT ANSWER!
SPEAK IN LITHUANIAN!
Terminai:
Vartoti ne "ląstelė", o "langelis".');
        return $taskData;
    }
}
?>