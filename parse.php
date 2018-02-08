<?php
/**
 * Název souboru: parse.php
 * Popis: Projekt 1 do předmětu IPP 2018/2019, FIT VUT
 * Vytvořil: David Hříbek
 * Datum: 8.2.18
 */
//include XMLWriter;
include_once XMLWriter::class;
//$test = new XMLWriter();
//$test = new Writer();

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
    public function writeArgumentFull($type, $value) {
        $this->xml->startElement('arg'.$this->instructionArgOrder); // pocatecni element
        //TODO
        $this->xml->endElement(); // ukoncujici element
    }

    /*
     * Zapise koncovy element do XML
     */
    public function writeEndElement() {
        $this->xml->endElement();
    }
}