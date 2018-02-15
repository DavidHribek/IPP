<?php
/**
 * Název souboru: parse.php
 * Popis: Projekt 1 do předmětu IPP 2018/2019, FIT VUT
 * Vytvořil: David Hříbek
 * Datum: 8.2.18
 */
$stats = new Statistics(); // Objekt pro sbirani dat o kodu (instrukce, komentare)
checkArguments($stats); // Kontrola argumentu scriptu
$inst = new Instruction($stats); // Objekt pro synt. a lexx. analyzu instrukci
$writer = new Writer(); // Objekt pro zapis XML

while ($argCount = $inst->loadNext()) {
    if (isset($inst->iName)) {
        $writer->writeInstructionStart($inst->iName); // zacatek elementu instruction
        //echo $inst->iName."\n";
        foreach ($inst->iArgs as $arg) {
            foreach ($arg as $type => $value)
                $writer->writeArgumentStartEnd($type, $value); // vypis jednotlivych argumentu
        }
        $writer->writeElementEnd(); // konec elementu instruction
    }
}
// vypis
fprintf(STDERR, "\n------START-------\n");
$writer->writeOut(); // Vypis XML na STDOUT
fprintf(STDERR, "------STATS-------\n");
$stats->writeOut();
fprintf(STDERR, "LOC   : ".$stats->countInstructions."\n");
fprintf(STDERR, "COMM  : ".$stats->countComments."\n");
fprintf(STDERR, "-------END-------\n\n");
/*--------------------------------------------------TRIDY/FUNKCE------------------------------------------------------*/
/*
 * Validace argumentu scriptu
 */
function checkArguments($stats) {
    global $argc;

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
 * Sber dat o poctu instrukci a komentaru v kodu
 */
class Statistics {
    public $countInstructions; // pocet radku s instrukcemi TODO PRIVATE
    public $countComments; // pocet radku na kterych se vyskytoval komentar TODO PRIVATE

    private $allowInstructions;
    private $allowComments;

    private $fileName;

    public function __construct() {
        $this->allowInstructions = false;
        $this->allowComments = false;

        $this->countInstructions = 0;
        $this->countComments = 0;

    }
    /*
     * Nastavi jmeno souboru pro vypis statistik
     */
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

    /*
     * Povoli vypis instrukci
     */
    public function allowInstructions() {
        $this->allowInstructions = true;
    }

    /*
     * Povoli vypis komentaru
     */
    public function allowComments() {
        $this->allowComments = true;
    }

    /*
     * Vytvori novy soubor s nazvem $fileName a zapise statisitky (pokud byly zadany v parametrech scriptu)
     */
    public function writeOut() {
        global $argv;
        if ($this->allowInstructions || $this->allowComments) {
            $toWrite = "";
            foreach ($argv as $opt) {
                if ($opt == '--loc')
                    $toWrite = $toWrite.$this->countInstructions."\n";
                if ($opt == '--comments')
                    $toWrite = $toWrite.$this->countComments."\n";
            }
            file_put_contents($this->fileName, rtrim($toWrite));
        }
    }

}

/*
 * Trida pro zpracovani instrukci
 */
class Instruction {
    private $stats; // statistiky
    private $called;

    public $iName; // nazev instrukce
    public $iArgs; // argumenty instrukce


    public function __construct($stats) {
        $this->stats = $stats;
        $this->called = 0;
    }

    /*
     * Nacte instrukci z STDIN
     * @return  true, false pokud neni co cist
     */
    public function loadNext() {
        $this->called++;
        $this->unsetInstructionVariables();

        if ( $line = fgets(STDIN) ) {
            if ($line != "\n")
                $this->stats->addInstruction(); // potencialni instrukce
        }
        else
            return false; // pokud neni co cist ze STDIN

        $line = preg_replace('/\s+/', ' ', $line); // odstraneni prebytecnych bilych znaku
//        echo $line."\n";
        $line = $this->removeComments($line);
        $line = trim($line); // odstraneni bilych znaku z okraju
//        echo $line."\n";
        $items = explode(' ', $line);
//        var_dump( $items);

        if ($this->called == 1) { // kontrola prvniho radku
            if (empty($items)) {
                fprintf(STDERR, "Missing first line .IPPcode18!\n");
                exit(21);
            }
            else {
                $items[0] = strtoupper($items[0]);
                if (((count($items) == 1) && ($items[0] == '.IPPCODE18'))) {
                    return true; // cti dalsi instrukci
                } else {
                    fprintf(STDERR, "Missing first line .IPPcode18!\n");
                    exit(21);
                }
            }
        }

        if (empty($items) || $items[0] == "") // pokud na radku neni instrukce, nacteme dalsi radek
            $this->loadNext();
        else {
            if ($this->checkSyntax($items)) {
                return true;
            }
            else {
                fprintf(STDERR, "Syntax or lexical Error!\n");
                exit(21);
            }
        }
        return true;
    }

    /*
     * Zkontroluje syntaxi dane instrukce, inicializuje promenne $iName a $iArgs
     * @return  true/false
     */
    private function checkSyntax($items) {
        if (!(count($items) >= 1 && count($items) <= 4)) // NESPRAVNY POCET ARGUMENTU
            return false;

        switch ($items[0] = strtoupper($items[0])) {
            case 'MOVE':        // <var> <symb>             OK
                if (count($items) == 3) {
                    $this->iName = $items[0];
                    return $this->checkVar($items[1]) && $this->checkSymb($items[2]);
                }
                break;
            case 'CREATEFRAME': // none                     OK
                if (count($items) == 1) {
                    $this->iName = $items[0];
                    return true;
                }
                break;
            case 'PUSHFRAME':   // none                     OK
                if (count($items) == 1) {
                    $this->iName = $items[0];
                    return true;
                }
                break;
            case 'POPFRAME':    // none                     OK
                if (count($items) == 1) {
                    $this->iName = $items[0];
                    return true;
                }
                break;
            case 'DEFVAR':      // <var>                    OK
                if (count($items) == 2) {
                    $this->iName = $items[0];
                    return $this->checkVar($items[1]);
                }
                break;
            case 'CALL':        // <label>                  OK
                if (count($items) == 2) {
                    $this->iName = $items[0];
                    return $this->checkLabel($items[1]);
                }
                break;
            case 'RETURN':      // none                     OK
                if (count($items) == 1) {
                    $this->iName = $items[0];
                    return true;
                }
                break;
            case 'PUSHS':       // <symb>                   OK
                if (count($items) == 2) {
                    $this->iName = $items[0];
                    return $this->checkSymb($items[1]);
                }
                break;
            case 'POPS':        // <var>                    OK
                if (count($items) == 2) {
                    $this->iName = $items[0];
                    return $this->checkVar($items[1]);
                }
                break;
            case 'ADD':         // <var> <symb1> <symb2>    OK
                if (count($items) == 4) {
                    $this->iName = $items[0];
                    return $this->checkVar($items[1]) && $this->checkSymb($items[2]) && $this->checkSymb($items[3]);
                }
                break;
            case 'SUB':         // <var> <symb1> <symb2>    OK
                if (count($items) == 4) {
                    $this->iName = $items[0];
                    return $this->checkVar($items[1]) && $this->checkSymb($items[2]) && $this->checkSymb($items[3]);
                }
                break;
            case 'MUL':         // <var> <symb1> <symb2>    OK
                if (count($items) == 4) {
                    $this->iName = $items[0];
                    return $this->checkVar($items[1]) && $this->checkSymb($items[2]) && $this->checkSymb($items[3]);
                }
                break;
            case 'IDIV':        // <var> <symb1> <symb2>    OK
                if (count($items) == 4) {
                    $this->iName = $items[0];
                    return $this->checkVar($items[1]) && $this->checkSymb($items[2]) && $this->checkSymb($items[3]);
                }
                break;
            case 'LT':          // <var> <symb1> <symb2>    OK
                if (count($items) == 4) {
                    $this->iName = $items[0];
                    return $this->checkVar($items[1]) && $this->checkSymb($items[2]) && $this->checkSymb($items[3]);
                }
                break;
            case 'GT':          // <var> <symb1> <symb2>    OK
                if (count($items) == 4) {
                    $this->iName = $items[0];
                    return $this->checkVar($items[1]) && $this->checkSymb($items[2]) && $this->checkSymb($items[3]);
                }
                break;
            case 'EQ':          // <var> <symb1> <symb2>    OK
                if (count($items) == 4) {
                    $this->iName = $items[0];
                    return $this->checkVar($items[1]) && $this->checkSymb($items[2]) && $this->checkSymb($items[3]);
                }
                break;
            case 'AND':         // <var> <symb1> <symb2>    OK
                if (count($items) == 4) {
                    $this->iName = $items[0];
                    return $this->checkVar($items[1]) && $this->checkSymb($items[2]) && $this->checkSymb($items[3]);
                }
                break;
            case 'OR':          // <var> <symb1> <symb2>    OK
                if (count($items) == 4) {
                    $this->iName = $items[0];
                    return $this->checkVar($items[1]) && $this->checkSymb($items[2]) && $this->checkSymb($items[3]);
                }
                break;
            case 'NOT':         // <var> <symb>             OK
                if (count($items) == 3) {
                    $this->iName = $items[0];
                    return $this->checkVar($items[1]) && $this->checkSymb($items[2]);
                }
                break;
            case 'INT2CHAR':    // <var> <symb>             OK
                if (count($items) == 3) {
                    $this->iName = $items[0];
                    return $this->checkVar($items[1]) && $this->checkSymb($items[2]);
                }
                break;
            case 'STRI2INT':    // <var> <symb1> <symb2>    OK
                if (count($items) == 4) {
                    $this->iName = $items[0];
                    return $this->checkVar($items[1]) && $this->checkSymb($items[2]) && $this->checkSymb($items[3]);
                }
                break;
            case 'READ':        // <var> <type>             OK
                if (count($items) == 3) {
                    $this->iName = $items[0];
                    return $this->checkVar($items[1]) && $this->checkType($items[2]);
                }
                break;
            case 'WRITE':       // <symb>                   OK
                if (count($items) == 2) {
                    $this->iName = $items[0];
                    return $this->checkSymb($items[1]);
                }
                break;
            case 'CONCAT':      // <var> <symb1> <symb2>    OK
                if (count($items) == 4) {
                    $this->iName = $items[0];
                    return $this->checkVar($items[1]) && $this->checkSymb($items[2]) && $this->checkSymb($items[3]);
                }
                break;
            case 'STRLEN':      // <var> <symb>             OK
                if (count($items) == 3) {
                    $this->iName = $items[0];
                    return $this->checkVar($items[1]) && $this->checkSymb($items[2]);
                }
                break;
            case 'GETCHAR':     // <var> <symb1> <symb2>    OK
                if (count($items) == 4) {
                    $this->iName = $items[0];
                    return $this->checkVar($items[1]) && $this->checkSymb($items[2]) && $this->checkSymb($items[3]);
                }
                break;
            case 'SETCHAR':     // <var> <symb1> <symb2>    OK
                if (count($items) == 4) {
                    $this->iName = $items[0];
                    return $this->checkVar($items[1]) && $this->checkSymb($items[2]) && $this->checkSymb($items[3]);
                }
                break;
            case 'TYPE':        // <var> <symb>             OK
                if (count($items) == 3) {
                    $this->iName = $items[0];
                    return $this->checkVar($items[1]) && $this->checkSymb($items[2]);
                }
                break;
            case 'LABEL':       // <label>                  OK
                if (count($items) == 2) {
                    $this->iName = $items[0];
                    return $this->checkLabel($items[1]);
                }
                break;
            case 'JUMP':        // <label>                  OK
                if (count($items) == 2) {
                    $this->iName = $items[0];
                    return $this->checkLabel($items[1]);
                }
                break;
            case 'JUMPIFEQ':    // <label> <symb1> <symb2>    OK
                if (count($items) == 4) {
                    $this->iName = $items[0];
                    return $this->checkLabel($items[1]) && $this->checkSymb($items[2]) && $this->checkSymb($items[3]);
                }
                break;
            case 'JUMPIFNEQ':   // <label> <symb1> <symb2>    OK
                if (count($items) == 4) {
                    $this->iName = $items[0];
                    return $this->checkLabel($items[1]) && $this->checkSymb($items[2]) && $this->checkSymb($items[3]);
                }
                break;
            case 'DPRINT':      // <symb>                   OK
                if (count($items) == 2) {
                    $this->iName = $items[0];
                    return $this->checkSymb($items[1]);
                }
                break;
            case 'BREAK':       // none                     OK
                if (count($items) == 1) {
                    $this->iName = $items[0];
                    return true;
                }
                break;
            default:
                return false;
        }
        return false;
    }

    /*
     * Kontrola syntaxe symbolu, vlozeni syntakticky spravneho argumentu do $iArgs
     * @return  true/false
     */
    private function checkSymb($symb) {
        if (preg_match('/^(int|bool|string)@.*$/', $symb)) {
            $symb = explode('@', $symb);
            //var_dump($symb);
            if ($symb[0] == 'int') {
                array_push($this->iArgs, [$symb[0] => $symb[1]]);
                return true;
            }
            elseif ($symb[0] == 'bool') {
                $symb[1] = strtolower($symb[1]);
                if (preg_match('/^(true|false)$/', $symb[1])) { // TODO sensitive???
                    array_push($this->iArgs, [$symb[0] => $symb[1]]);
                    return true;
                }
            }
            else { // 'string'
                if (preg_match('/^.*$/', $symb[1])) { // TODO ????
                    array_push($this->iArgs, [$symb[0] => $symb[1]]);
                    return true;
                }
            }
        }
        elseif (preg_match('/^(GF|LF|TF)@.*$/', $symb)) {
            return $this->checkVar($symb);
        }
        return false;
    }

    /*
     * Kontrola syntaxe navesti, vlozeni syntakticky spravneho argumentu do $iArgs
     * @return  true/false
     */
    private function checkLabel($label) {
        if (preg_match('/^(_|-|\$|&|\*|\w)[\d\w]*$/', $label)) {
            array_push($this->iArgs, ['label' => $label]);
            return true;
        }
        else
            return false;
    }

    /*
     * Kontrola syntaxe typu, vlozeni syntakticky spravneho argumentu do $iArgs
     * @return  true/false
     */
    private function checkType($type) {
        if (preg_match('/^(int|bool|string)$/', $type)) {
            array_push($this->iArgs, ['type' => $type]);
            return true;
        }
        else
            return false;
    }

    /*
     * Kontrola syntaxe promenne
     * @return  true/false
     */
    private function checkVar($var) {
        if (preg_match('/^(GF|LF|TF)@(_|-|\$|&|\*|\w)[\d\w]*$/', $var)) {
            array_push($this->iArgs, ['var' => $var]);
            return true;
        }
        else
            return false;
    }

    /*
     * Odstrani komentare radku, sbira statistiky o poctu komentaru atd..
     * @return   Pole retezcu bez komentaru
     */
    private function removeComments($line) {
        if (strpos($line, '#') !== false) {
            $line = explode('#', $line);
            $line = $line[0];
            $this->stats->addComment();
        }
        if ($line == "") // pokud radek obsahoval pouze komentar, nepocita se mezi instrukce
            $this->stats->subInstruction();
        return $line;
    }

    /*
     * Inicializace promennych instrukce
     */
    private function unsetInstructionVariables() {
        unset($this->iName);
        $this->iArgs = [];
    }
}

/*
 * Trida pro zjednoduseni generovani XML pro dany jazyk
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
