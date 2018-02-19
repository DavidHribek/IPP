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

        $this->generateFiles(); // vygeneruje chybejici soubory
    }

    /*
     * Vygeneruje chybejici soubory
     */
    private function generateFiles() {
        $rcPath = (count($this->rcFiles) > 0)? $this->getDirectoryPath($this->rcFiles[0]): $this->baseDir;
        $inPath = (count($this->inFiles) > 0)? $this->getDirectoryPath($this->inFiles[0]): $this->baseDir;
        $outPath = (count($this->outFiles) > 0)? $this->getDirectoryPath($this->outFiles[0]): $this->baseDir;

        // generovani .rc souboru
        foreach ($this->srcFiles as $file) {
            $expectedFile = $rcPath.$this->getFileName($file).".rc";
            if (!file_exists($expectedFile)) { // vytvori soubor s rc 0, pokud soubor $file neexistuje v slozce rc souboru
//                file_put_contents($expectedFile, "0"); // TODO uncomment
                fprintf(STDERR, "Created new file: ".$expectedFile."\n");
            }
        }

        // generovani .in souboru
        foreach ($this->srcFiles as $file) {
            $expectedFile = $inPath.$this->getFileName($file).".in";
            if (!file_exists($expectedFile)) { // vytvori soubor s rc 0, pokud soubor $file neexistuje v slozce rc souboru
//                file_put_contents($inPath.$this->getFileName($file).".in", ""); // TODO uncomment
                fprintf(STDERR, "Created new file: ".$expectedFile."\n");
            }
        }

        // generovani .out souboru
        foreach ($this->srcFiles as $file) {
            $expectedFile = $outPath.$this->getFileName($file).".out";
            if (!file_exists($expectedFile)) { // vytvori soubor s rc 0, pokud soubor $file neexistuje v slozce rc souboru
//                file_put_contents($outPath.$this->getFileName($file).".out", ""); // TODO uncomment
                fprintf(STDERR, "Created new file: ".$expectedFile."\n");
            }
        }

    }

    /*
     * Vraci nazev souboru
     */
    private function getFileName($pathToFile) {
        return preg_replace('/^(.*\/)?(.+)\.(in|out|rc|src)$/','\2', $pathToFile);
    }

    /*
     * Vraci cestu ke slozce, ve ktere se nachazi dany soubor
     */
    private function getDirectoryPath($pathToFile) {
        return preg_replace('/^(.*\/).+\.(in|out|rc|src)$/','\1', $pathToFile);
    }
}

/*
 * Trida pro kontrolu a zpracovani argumentu scriptu
 */
class Arguments {
    public $recursive;
    public $directory;
    public $parseScript;
    public $intScript;

    public function __construct() {
        $this->recursive = false;
        $this->directory = './';
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
                if (substr($opts['directory'], -1) != '/') // pokud posledni znak neni /, pridat / nakonec
                    $opts['directory'] = $opts['directory']."/";
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
            fprintf(STDERR, "File does not exist: ".$this->parseScript."\n");
            exit(10);
        }
        if (!file_exists($this->intScript)) { // kontrola existence souboru interpret.py
            fprintf(STDERR, "File does not exist: ".$this->intScript."\n");
            exit(10);
        }
        if (!file_exists($this->directory)) { // kontrola existence slozky
            fprintf(STDERR, "Directory does not exist: ".$this->directory."\n");
            exit(10);
        }
    }
}