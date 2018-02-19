<?php
/**
 * Název souboru: test.php
 * Popis: Projekt 1 do předmětu IPP 2018/2019 (testy), FIT VUT
 * Vytvořil: David Hříbek
 * Datum: 8.2.18
 */

$Arguments = new Arguments();
$Arguments->checkArguments();

$DirectoryScanner = new DirectoryScanner($Arguments->directory);
$DirectoryScanner->scan($Arguments->directory, $Arguments->recursive);







//var_dump($Regex);










/*--------------------------------------------------TRIDY/FUNKCE------------------------------------------------------*/

class DirectoryScanner {

    private $srcFiles;
    private $rcFiles;
    private $inFiles;
    private $outFiles;

    private $baseDir;


    public function __construct($baseDir) {
        $this->srcFiles = [];
        $this->rcFiles = [];
        $this->inFiles = [];
        $this->outFiles = [];

        $this->baseDir = $baseDir;
    }

    /*
     * Naskenuje danou slozku, ulozi cesty k .src .in .out .rc souborum do promennych, vygeneruje prazdne soubory (pokud je treba)
     */
    public function scan($dir, $recursive) {
        if ($recursive) {
            $Directory = new RecursiveDirectoryIterator($dir);
            $Iterator = new RecursiveIteratorIterator($Directory);
        }
        else {
            $Directory = new DirectoryIterator($dir);
            $Iterator = new IteratorIterator($Directory);
        }

        // soubory .src
        $Regex = new RegexIterator($Iterator, '/^.+\.src$/i', RecursiveRegexIterator::GET_MATCH);
        foreach ($Regex as $r)
            array_push($this->srcFiles, $r[0]);
        sort($this->srcFiles);

        // soubory .rc
        $Regex = new RegexIterator($Iterator, '/^.+\.rc$/i', RecursiveRegexIterator::GET_MATCH);
        foreach ($Regex as $r)
            array_push($this->rcFiles, $r[0]);
        sort($this->rcFiles);

        // soubory .in
        $Regex = new RegexIterator($Iterator, '/^.+\.in$/i', RecursiveRegexIterator::GET_MATCH);
        foreach ($Regex as $r)
            array_push($this->inFiles, $r[0]);
        sort($this->inFiles);

        // soubory .out
        $Regex = new RegexIterator($Iterator, '/^.+\.out$/i', RecursiveRegexIterator::GET_MATCH);
        foreach ($Regex as $r)
            array_push($this->outFiles, $r[0]);
        sort($this->outFiles);

        if (count($this->srcFiles) == 0) { // test musi mit k dispozici zdrojove soubory
            fprintf(STDERR, "No source files!\n");
            exit(10);
        }

        $this->generateFiles();
    }

    private function generateFiles() {
        $rcPath = $this->getDirectoryPath($this->rcFiles[0]);
        $inPath = $this->getDirectoryPath($this->inFiles[0]);
        $outPath = $this->getDirectoryPath($this->outFiles[0]);
        echo $rcPath;

        foreach ($this->inFiles as $file) {
//            if (file_exists($this->getDirectoryPath($file)))
            echo $this->getFileName($file)."\n";
        }
    }

    /*
     * Vraci nazev souboru
     */
    private function getFileName($pathToFile) {
        return preg_replace('/^(.*\/)(.+\.(in|out|rc|src))$/','\2', $pathToFile);
    }

    /*
     * Vraci cestu k slozce, ve ktere se nachazi dany soubor
     */
    private function getDirectoryPath($pathToFile) {
        return preg_replace('/^(.*\/).+\.(in|out|rc|src)$/','\1', $pathToFile);
    }

//    public function
}




/*
 * Trida pro kontrolu a zpracovani argumentu scriptu
 */
class Arguments
{
    public $recursive;
    public $directory;
    public $parseScript;
    public $intScript;

    public function __construct() {
        $this->recursive = false;
        $this->directory = '.';
        $this->parseScript = 'parse.php';
        $this->intScript = 'interpret.py';
    }

    /*
     * Validace argumentu scriptu
     */
    function checkArguments() {
        global $argc;
        global $argv;

        $validArgs = 0;

        $errorMsg = "Not allowed arguments!\n";

        $opts = getopt("", ["help", "directory:", "recursive", "parse-script:", "int-script:"]);

        if ($argc == 1) { // zadny argument
            return; // OK
        } else if ($argc >= 2 && $argc <= 6) { // jeden az sest argumentu
            if (array_key_exists('help', $opts)) {
                fprintf(STDERR, "HELP TEXT TODO\n"); // TODO
                exit(0);
            }
            if (array_key_exists('recursive', $opts)) {
                $this->recursive = true;
                $validArgs++;
            }
            if (array_key_exists('directory', $opts)) {
                $this->directory = $opts['directory'];
                $validArgs++;
            }
            if (array_key_exists('parse-script', $opts)) {
                $this->parseScript = $opts['parse-script'];
                $validArgs++;
            }
            if (array_key_exists('int-script', $opts)) {
                $this->intScript = $opts['int-script'];
                $validArgs++;
            }
            if (!($validArgs == (count($argv) - 1))) { // pokud byly zadany dalsi nechtene arguemnty: chyba
                fprintf(STDERR, $errorMsg);
                exit(10);
            }
        } else { // nespravny pocet argumentu
            fprintf(STDERR, "Bad arguments count!\n");
            exit(10);
        }

        if (!file_exists($this->parseScript)) { // kontrola existence souboru parse.php
            fprintf(STDERR, "File '".$this->parseScript."' does not exist!\n");
            exit(10);
        }
        if (!file_exists($this->intScript)) { // kontrola existence souboru interpret.py
            fprintf(STDERR, "File '".$this->intScript."' does not exist!\n");
            exit(10);
        }
        if (!file_exists($this->directory)) { // kontrola existence slozky
            fprintf(STDERR, "Directory '".$this->directory."' does not exist!\n");
            exit(10);
        }
    }
}