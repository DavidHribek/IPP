<?php
/**
 * Název souboru: parse.php
 * Popis: Projekt 1 do předmětu IPP 2018/2019, FIT VUT
 * Vytvořil: David Hříbek
 * Datum: 8.2.18
 */
checkArguments();

$inst = new Instruction();

while ($inst->getNext()) {
}

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



/*
 * FUnkce pro validaci argumentu scriptu
 */
function checkArguments() {
    global $argc, $argv;
    if ($argc == 1) {
        return; // OK
    }
    elseif ($argc == 2) {
        if ($argv[1] == '--help') {
            echo "HELP TEXT TODO\n"; // TODO STDERR
            exit(0);
        }
        else {
            echo $argv[1]." IS NOT ALOWED ARGUMENT\n"; // TODO STDERR
            exit(10);
        }
    }
}

/*
 * Zpracovani instrukce
 */
class Instruction {
    // instrukce
    private $iName; // nazev instrukce

    public $iArg1t; // typ arg1
    public $iArg2t; // typ arg2
    public $iArg3t; // typ arg3

    public $iArg1v; // hodnota arg1
    public $iArg2v; // hodnota arg2
    public $iArg3v; // hodnota arg3

    // statistitky
    private $countLine; // pocet radku s instrukcemi
    private $countEmptyLine; // pocet prazdnych radku *asi se nepouzije*
    private $countCommentLine; // pocet radku na kterych se vyskytoval komentar

    /*
     * Konstruktor
     */
    public function Instruction() {
        $this->countLine = 0;
        $this->countEmptyLine = 0;
        $this->countCommentLine = 0;
    }

    /*
     * Nacte instrukci ze vstupu
     * Vraci:   Pocet argumentu    Pokud je instrukce syntakticky spravne
     *          FALSE   Jinak
     */
    public function getNext() {
        $this->unsetInstructionVariables();

        if ($line = stream_get_line(STDIN,0, "\n"))
            $this->countLine++;
        else
            return false; // pokud neni co cist ze STDIN


        $line = preg_replace('/\s+/', ' ', $line); // odstraneni prebytecnych bilych znaku
        $line = trim($line); // odstraneni bilych znaku z okraju

        $items = explode(' ', $line);
        $items = $this->removeComments($items);
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
                $this->countCommentLine++;
                return $newItems;
            }
            else
                array_push($newItems, $item);
        }
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
