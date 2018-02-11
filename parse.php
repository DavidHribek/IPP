<?php
/**
 * Název souboru: parse.php
 * Popis: Projekt 1 do předmětu IPP 2018/2019, FIT VUT
 * Vytvořil: David Hříbek
 * Datum: 8.2.18
 */
checkArguments();



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
            echo "NOT ALOWED ARGUMENTS\n"; // TODO STDERR
            exit(10);
        }
    }
}

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
