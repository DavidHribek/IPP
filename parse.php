<?php
/**
 * Název souboru: parse.php
 * Popis: Projekt 1 do předmětu IPP 2018/2019, FIT VUT
 * Vytvořil: David Hříbek
 * Datum: 8.2.18
 */
$stats = new Statistics();
checkArguments($stats);
$inst = new Instruction($stats);


//while ($inst->loadNext()) {}

//$instruction->getNext();












// test vypisu
//$writer = new Writer(); // xml writer
//$writer->writeInstructionStart('DEFVAR');
//
//$writer->writeArgumentStartEnd('label', 'while');
//$writer->writeArgumentStartEnd('type', 'int');
//$writer->writeArgumentStartEnd('var', 'gf@cislo');
//$writer->writeArgumentStartEnd('int', '42');
//$writer->writeArgumentStartEnd('bool', 'True');
//$writer->writeArgumentStartEnd('string', 'counter\032obsahuje\032');
//
//$writer->writeElementEnd();
//$writer->writeOut();

fprintf(STDERR, "---------------\n");
fprintf(STDERR, "LOC   : ".$stats->countInstructions."\n");
fprintf(STDERR, "COMM  : ".$stats->countComments."\n");
fprintf(STDERR, "Name  : ".$stats->fileName."\n");

/*--------------------------------------------------TRIDY/FUNKCE------------------------------------------------------*/
/*
 * Funkce pro validaci argumentu scriptu
 */
function checkArguments($stats) {
    global $argc, $argv;

    $errorMsg = "Not allowed arguments!\n";

    $opts = getopt("",["help", "stats:", "comments", "loc"]);

    if ($argc == 1) { // zadny argument
        return; // OK
    }
    elseif ($argc == 2) { // jeden argument
        if (array_key_exists('help', $opts)) {
            fprintf(STDERR,"HELP TEXT TODO\n");
            exit(0);
        }
        elseif (array_key_exists('stats', $opts)) {
            $stats->setFileName($opts['stats']);
            $stats->allowInstructions();
            $stats->allowComments();
            return; // OK
        }
        else {
            fprintf(STDERR, $errorMsg);
            exit(10);
        }
    }
    elseif ($argc == 3) { // dva argumenty
        if (array_key_exists('stats', $opts) && array_key_exists('comments', $opts)) {
            $stats->setFileName($opts['stats']);
            $stats->allowComments();
        }
        elseif (array_key_exists('stats', $opts) && array_key_exists('loc', $opts)) {
            $stats->setFileName($opts['stats']);
            $stats->allowInstructions();
        }
        else {
            fprintf(STDERR, $errorMsg);
            exit(10);
        }
    }
    elseif ($argc == 4) { // tri arguemnty
        if (array_key_exists('stats', $opts) && array_key_exists('comments', $opts) && array_key_exists('loc', $opts)) {
            $stats->setFileName($opts['stats']);
            $stats->allowInstructions();
            $stats->allowComments();
        }
        else {
            fprintf(STDERR, $errorMsg);
            exit(10);
        }
    }
    else {
        fprintf(STDERR,"Bad arguments count!\n");
        exit(10);
    }
}

/*
 * Zajistuje sber dat o poctu instrukci a komentaru
 */
class Statistics {
    public $countInstructions; // pocet radku s instrukcemi TODO PRIVATE
    public $countComments; // pocet radku na kterych se vyskytoval komentar TODO PRIVATE

    private $allowInstructions;
    private $allowComments;

    public $fileName; // TODO PRIVATE

    public function __construct() {
        $this->allowInstructions = false;
        $this->allowComments = false;

        $this->countInstructions = 0;
        $this->countComments = 0;

    }

    public function setFileName($name) {
        $this->fileName = $name;
    }

    public function addInstruction() {
        $this->countInstructions++;
    }

    public function subInstruction() {
        $this->countInstructions--;
    }

    public function addComment() {
        $this->countComments++;
    }

    public function allowInstructions() {
        $this->allowInstructions = true;
    }

    public function allowComments() {
        $this->allowComments = true;
    }

    public function printStatistics() {
        // TODO
    }

}

/*
 * Zpracovani instrukce
 */
class Instruction {
    private $stats; // statistiky
    // instrukce
    private $iName; // nazev instrukce

    public $iArg1t; // typ arg1
    public $iArg2t; // typ arg2
    public $iArg3t; // typ arg3

    public $iArg1v; // hodnota arg1
    public $iArg2v; // hodnota arg2
    public $iArg3v; // hodnota arg3

    public function __construct($stats) {
        $this->countInstLine = 0;
        $this->countCommentLine = 0;
        $this->stats = $stats;
    }

    /*
     * Nacte instrukci z STDIN
     * Vraci:   Pocet argumentu    Pokud je instrukce syntakticky spravne
     *          FALSE   Jinak
     */
    public function loadNext() {
        $this->unsetInstructionVariables();

//        if ($line = stream_get_line(STDIN,0, "\n"))
        if ( $line = fgets(STDIN) ) {
            if ($line != "\n")
                $this->stats->addInstruction();
        }
        else
            return false; // pokud neni co cist ze STDIN

        $line = preg_replace('/\s+/', ' ', $line); // odstraneni prebytecnych bilych znaku
        $line = trim($line); // odstraneni bilych znaku z okraju
        $items = explode(' ', $line);
        $items = $this->removeComments($items);

        if (empty($items) || $items[0] == "") // pokud na radku neni instrukce, nacteme dalsi radek
            $this->loadNext();
        else
            var_dump($items);
        return true;
    }

    /*
     * Odstrani komentare radku, sbira statistiky o poctu komentaru atd..
     * Vraci:   Pole slov bez komentaru
     */
    private function removeComments($items) {
        $newItems = [];
        foreach ($items as $item) {
            if ( ereg('^#.*', $item) ) {
                $this->stats->addComment();
                break; // vse za znakem # zahazujeme
            }
            else
                array_push($newItems, $item);
        }
        if (empty($newItems)) // pokud radek obsahuje pouze komentare, nepocita se mezi instrukce
            $this->stats->subInstruction();
        return $newItems;
    }

    /*
     * Inicializace promennych instrukce
     */
    private function unsetInstructionVariables() {
        unset($this->iName);

        unset($this->iArg1t);
        unset($this->iArg2t);
        unset($this->iArg3t);

        unset($this->iArg1v);
        unset($this->iArg2v);
        unset($this->iArg3v);
    }
}

/*
 * Trida pro zjednoduseni generovani XML
 */
class Writer {
    private $xml; // instance XMLWriter
    private $instructionOrder; // pocitadlo poradi instrukci
    private $instructionArgOrder; // pocitadlo poradi argumentu aktualni instrukce

    /*
     * Konstruktor
     */
    public function Writer() {
        $this->instructionOrder = 1; // prvni instrukce ma cislo 1

        $this->xml = new XMLWriter();
        $this->xml->openMemory();
        $this->xml->setIndent(4);

        // inicializace hlavicky XML
        $this->xml->startDocument('1.0', 'UTF-8');
        $this->xml->startElement('program');
            $this->xml->writeAttribute('language', 'IPPcode18');
    }

    /*
     * Zapis elementu INSTRUCTION do XML
     * Argumenty:
     *      $opcode:    Nazev instrukce
     */
    public function writeInstructionStart($opcode) {
        $this->instructionArgOrder = 1; // prvni vygenerovany argument ma cislo 1
        $opcode = strtoupper($opcode);
        // vypsani instrukce do XML
        $this->xml->startElement('instruction'); // zapis instrukce
        $this->xml->writeAttribute('order', $this->instructionOrder); // zapis atributu order
        $this->xml->writeAttribute('opcode', $opcode); // zapis atributu opcode
        $this->instructionOrder++; // inkrementace order pro vypis dalsi instrukce
    }

    /*
     * Zapis elementu ARGX do XML vcetne ukoncovace
     * Argumenty:
     *      $type:      Typ argumentu (int, bool, string, label, type, var)
     *      $value:     Hodnota argumentu
     */
    public function writeArgumentStartEnd($type, $value) {
        $type = strtolower($type);
        $this->xml->startElement('arg'.$this->instructionArgOrder); // pocatecni element
        $this->xml->writeAttribute('type', $type);

        // kontrola
        if ($type == 'bool') // bool, false vzdy malymi pismeny
            $value = strtolower($value);
        elseif ($type == 'var') { // oznaceni ramce GF, LF.. vzdy velkymi pismeny
            $value[0] = strtoupper($value[0]);
            $value[1] = strtoupper($value[1]);
        }

        $this->xml->text($value); // hodnota elementu
        $this->xml->endElement(); // ukoncujici element
        $this->instructionArgOrder++; // inkrementace order pro vypis dalsiho argumentu
    }

    /*
     * Zapise koncovy element do XML
     */
    public function writeElementEnd() {
        $this->xml->endElement();
    }

    /*
     * Vypis XML na STDOUT
     */
    public function writeOut() {
        $this->xml->endDocument();
        echo $this->xml->outputMemory();
    }
}
