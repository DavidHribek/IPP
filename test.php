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
$TmpFile = new TemporaryFile();
$HtmlGenerator = new HtmlGenerator();

$TmpFile->create();
foreach ($DirectoryScanner->srcFiles as $srcFile)
{
    // pridruzene soubory .rc .in .out k aktualnimu .src
    $rcFile = array_shift($DirectoryScanner->rcFiles);
    $inFile = array_shift($DirectoryScanner->inFiles);
    $outFile = array_shift($DirectoryScanner->outFiles);

//    echo "\n\nFIL NAME: ".$srcFile."\n"; // DEBUG
//    echo "FILE: |".$TmpFile->getAsString()."|\n"; // DEBUG
    unset($parseOutput);
    exec('php5.6 '.$Arguments->parseScript.' < '.$srcFile, $parseOutput, $parseReturnCode); // parse.php < soubor.src
    if ($parseReturnCode == 0) // nasleduje interpretace
    {
        $TmpFile->writeExecOutput($parseOutput); // naplni tmp soubor vystupem z parseru
        unset($interpretOutput);
        exec('python3.6 '.$Arguments->intScript.' --source='.$TmpFile->getPath().' < '.$inFile, $interpretOutput, $interpretReturnCode); // interpret.py < XML
        $TmpFile->reset();
        $TmpFile->writeExecOutput($interpretOutput); // naplni tmp soubor vystupem z interpretu
//        echo "INT OUTP: |".$TmpFile->getAsString()."|\n"; // DEBUG
//        echo "REF OUTP: |".file_get_contents($outFile)."|\n"; // DEBUG
        if ($interpretReturnCode == 0)
        {
            exec('diff '.$TmpFile->getPath().' '.$outFile, $output /*dale nepouzito*/, $diffReturnCode); // porovnani vystupu interpretu a .out soboru
            if ($diffReturnCode == 0) // vystup interpretu == .out soubor
            {
                $HtmlGenerator->addTestResult($srcFile, $inFile, $outFile, $rcFile, true, 0, 0, 'correct');
//                echo "Inter: PASS output"; // TODO HTML
            }
            else
            {
//                echo "Inter: ERROR output"; // TODO HTML
                $HtmlGenerator->addTestResult($srcFile, $inFile, $outFile, $rcFile, false, 0, 0, 'incorrect');
            }
        }
        else // chyba interpretace
        {
            if ($interpretReturnCode == file_get_contents($rcFile)) // chybove kody se shoduji
            {
                $HtmlGenerator->addTestResult($srcFile, $inFile, $outFile, $rcFile, true, 0, $interpretReturnCode, 'notset');
//                echo "Inter: PASS exit code"; // TODO HTML
            }
            else // chybove kody se neshoduji
            {
                $HtmlGenerator->addTestResult($srcFile, $inFile, $outFile, $rcFile, false, 0, $interpretReturnCode, 'notset');
//                echo "Inter: ERROR exit code ".$interpretReturnCode." expected ".file_get_contents($rcFile); // TODO HTML
            }
        }
    }
    else // chyba parsovani
    {
        if ($parseReturnCode == file_get_contents($rcFile)) // chybove kody se shoduji
        {
            $HtmlGenerator->addTestResult($srcFile, $inFile, $outFile, $rcFile, false, $parseReturnCode, -1, 'notset');
//            echo "Parse: PASS exit code"; // TODO HTML
        }
        else // chybove kody se neshoduji
        {
            $HtmlGenerator->addTestResult($srcFile, $inFile, $outFile, $rcFile, false, $parseReturnCode, -1, 'notset');
//            echo "Parse: ERROR exit code ".$parseReturnCode." expected ".file_get_contents($rcFile); // TODO
        }
    }
}
$TmpFile->close();

$HtmlGenerator->generate();

/*--------------------------------------------------TRIDY/FUNKCE------------------------------------------------------*/

class HtmlGenerator {
    private $testResults;

    public function __construct()
    {
        $this->testResults = [];
    }

    /*
     * Vlozi vysledek testu do promenne $testResult
     */
    public function addTestResult($srcFile, $infile, $outFile, $rcFile, $pass, $parseReturnCode, $interpretReturnCode, $interpretOutput)
    {
        $this->testResults[] = [
            'srcFile' => $srcFile,
            'inFile' => $infile,
            'outFile' => $outFile,
            'rcFile' => $rcFile,
            'parseReturnCode' => $parseReturnCode,
            'interpretReturnCode' => $interpretReturnCode,
            'interpretOutput' => $interpretOutput,
            'pass' => $pass
        ];
    }

    /*
     * Vygeneruje html stranku na STDOUT
     */
    public function generate()
    {
        $html = '<!doctype html>
        <html lang=\"cz\">
        <head>
            <meta charset=\"utf-8\">
            <title>IPPcode18 Test</title>
            <meta name=\"Přehled testování scriptů parse.php a interpret.py\">
            <meta name=\"David Hříbek\">
            
            <style>
            table {
            
                font-family: Helvetica, Arial, Helvetica, sans-serif;
                border-collapse: collapse;
                width: 70%;
                margin: auto;
            }
            
            table td, table th {
                border: 1px solid #ddd;
                padding: 8px;
            }
            
            table tr:nth-child(even){background-color: #f2f2f2;}
            
            table tr:hover {background-color: #ddd;}
            
            table th {
                padding-top: 12px;
                padding-bottom: 12px;
                text-align: left;
                background-color: #2dbb73;
                color: white;
                text-align: center;
            }
            
            #circle {
              width: 20px;
              height: 20px;
              -webkit-border-radius: 25%;
              -moz-border-radius: 25%;
              border-radius: 100%;
              margin: auto;
            }
            .no-output {
                background: #c3baba;
            }
            .failed {
                background: #bb3737;
            }
            .passed {
                background: #2dbb73;
            }
            .center {
                text-align: center;
            }
        </style>
        </head>

        <body>
            <div>
                <table>
                    <tr>
                        <th rowspan="2">IPPcode18 Source file</th>
                        <th rowspan="2">Other files</th>
                        <th colspan="2">Parse return code</th>
                        <th colspan="2">Interpret return code</th>
                        <th rowspan="2">Interpret Output</th>
                        <th rowspan="2">Result</th>
                    </tr>
                    <tr>
                        <th>Expected</th>
                        <th>Returned</th>
                        <th>Expected</th>
                        <th>Returned</th>                        
                    </tr>';

        foreach ($this->testResults as $testResult)
        {
            $html = $html."<tr>";
            // src file
            $html = $html."<td>".$testResult['srcFile']."</td>\n";
            // other files
            $html = $html."<td>".$testResult['inFile']."</br>".$testResult['outFile']."</br>".$testResult['rcFile']."</td>\n";
            // parse return code
            if ($testResult['interpretOutput'] == 'correct')
            {
//                $interpretExpectedReturnCode // TODO
            }

            if (($rc = file_get_contents($testResult['rcFile'])) == "0")
                $parserExpectedReturnCode = $interpretExpectedReturnCode = 0;
            else
            {
                if(in_array($rc, ['31', '32', '52', '53', '54', '55', '56', '57', '58'])) // pocita se s chybou az v interpreteru
                {
                    $parserExpectedReturnCode = 0;
                    $interpretExpectedReturnCode = $rc;
                }
                elseif (in_array($rc, ['21'])) // pocita se s chybou v parseru
                {
                    $parserExpectedReturnCode = $rc;
                    $interpretExpectedReturnCode = "X";
                }
            }
            $html = $html."<td class='center'>".$parserExpectedReturnCode."</td>\n";
            $html = $html."<td class='center'>".$testResult['parseReturnCode']."</td>\n";
            $html = $html."<td class='center'>".$interpretExpectedReturnCode."</td>\n";
            $html = $html."<td class='center'>".$testResult['interpretReturnCode']."</td>\n";
            // INTERPRET OUTPUT
            if ($testResult['pass'])
                $html = $html."<td><div id='circle' class='passed'></div></td>\n";
            else
                $html = $html."<td><div id='circle' class='failed'></div></td>";
            $html = $html."</tr>\n";
            // OK/FAIL
            if ($testResult['pass'])
                $html = $html."<td><div id='circle' class='passed'></div></td>\n";
            else
                $html = $html."<td><div id='circle' class='failed'></div></td>";
            $html = $html."</tr>\n";
        }

        $html = $html.'</table>
            </div>
            <script></script>
        </body>
        </html>';

        echo $html;
//        fprintf(STDOUT, $html."\n");
    }

}

/*
 * Trida pro praci s docasnym souborem
 */
class TemporaryFile
{
    private $file;

    /*
     * Otevreni docasneho souboru
     */
    public function create()
    {
        $this->file = tmpfile();
    }

    /*
     * Uzavreni docasneho souboru
     */
    public function close()
    {
        fclose($this->file);
    }

    /*
     * Premaze soubor
     */
    public function reset()
    {
        $this->close();
        $this->create();
    }

    /*
     * Vrati obsah souboru jako string
     */
    public function getAsString()
    {
        $metaDatas = stream_get_meta_data($this->file);
        $tmpFilename = $metaDatas['uri'];
        return file_get_contents($tmpFilename);
    }

    /*
     * Vrati cestu k souboru
     */
    public function getPath()
    {
        $metaDatas = stream_get_meta_data($this->file);
        return $metaDatas['uri'];
    }

    /*
     * Zapis polozek pole do souboru
     */
    public function writeExecOutput($array)
    {
//        file_put_contents($this->getPath(), implode("\n", $array));
        fwrite($this->file, implode("\n", $array));
    }
}


/*
 * Trida pro skenovani slozky, ziskani nazvu souboru, generovani novych souboru (pokud chybi)
 */
class DirectoryScanner
{
    public $srcFiles;
    public $rcFiles;
    public $inFiles;
    public $outFiles;

    private $baseDir;


    public function __construct($baseDir)
    {
        $this->srcFiles = [];
        $this->rcFiles = [];
        $this->inFiles = [];
        $this->outFiles = [];

        $this->baseDir = $baseDir;
    }

    /*
     * Naskenuje danou slozku, ulozi cesty k .src .in .out .rc souborum do promennych, vygeneruje prazdne soubory (pokud je treba)
     */
    public function scan($dir, $recursive)
    {
        if ($recursive) // pokud byl zadan argument --recursive
        {
            $Directory = new RecursiveDirectoryIterator($dir);
            $Iterator = new RecursiveIteratorIterator($Directory);
        }
        else
        {
            $Directory = new DirectoryIterator($dir);
            $Iterator = new IteratorIterator($Directory);
        }

        // soubory .src
        $Regex = new RegexIterator($Iterator, '/^.+\.src$/i', RecursiveRegexIterator::GET_MATCH);
        foreach ($Regex as $r)
            array_push($this->srcFiles, $r[0]);

        // soubory .rc
        $Regex = new RegexIterator($Iterator, '/^.+\.rc$/i', RecursiveRegexIterator::GET_MATCH);
        foreach ($Regex as $r)
            array_push($this->rcFiles, $r[0]);

        // soubory .in
        $Regex = new RegexIterator($Iterator, '/^.+\.in$/i', RecursiveRegexIterator::GET_MATCH);
        foreach ($Regex as $r)
            array_push($this->inFiles, $r[0]);

        // soubory .out
        $Regex = new RegexIterator($Iterator, '/^.+\.out$/i', RecursiveRegexIterator::GET_MATCH);
        foreach ($Regex as $r)
            array_push($this->outFiles, $r[0]);

        if (count($this->srcFiles) == 0)
        { // test musi mit k dispozici zdrojove soubory
            fprintf(STDERR, "No source files!\n");
            exit(0);
        }

        $this->generateFiles(); // vygeneruje chybejici soubory

        // seradit soubory
        sort($this->srcFiles);
        sort($this->rcFiles);
        sort($this->inFiles);
        sort($this->outFiles);

//        var_dump($this->srcFiles);
//        var_dump($this->rcFiles);
//        var_dump($this->inFiles);
//        var_dump($this->outFiles);
    }

    /*
     * Vygeneruje chybejici soubory
     */
    private function generateFiles()
    {
        $rcPath = (count($this->rcFiles) > 0)? $this->getDirectoryPath($this->rcFiles[0]): $this->baseDir;
        $inPath = (count($this->inFiles) > 0)? $this->getDirectoryPath($this->inFiles[0]): $this->baseDir;
        $outPath = (count($this->outFiles) > 0)? $this->getDirectoryPath($this->outFiles[0]): $this->baseDir;

        // generovani .rc souboru
        foreach ($this->srcFiles as $file)
        {
            $expectedFile = $rcPath.$this->getFileName($file).".rc";
            if (!file_exists($expectedFile))
            { // vytvori soubor s rc 0, pokud soubor $file neexistuje v slozce rc souboru
                file_put_contents($expectedFile, "0"); // TODO uncomment
                array_push($this->rcFiles, $expectedFile);
                fprintf(STDERR, "Created new file: ".$expectedFile."\n");
            }
        }

        // generovani .in souboru
        foreach ($this->srcFiles as $file)
        {
            $expectedFile = $inPath.$this->getFileName($file).".in";
            if (!file_exists($expectedFile))
            { // vytvori soubor s rc 0, pokud soubor $file neexistuje v slozce rc souboru
                file_put_contents($inPath.$this->getFileName($file).".in", ""); // TODO uncomment
                array_push($this->inFiles, $expectedFile);
                fprintf(STDERR, "Created new file: ".$expectedFile."\n");
            }
        }

        // generovani .out souboru
        foreach ($this->srcFiles as $file)
        {
            $expectedFile = $outPath.$this->getFileName($file).".out";
            if (!file_exists($expectedFile))
            { // vytvori soubor s rc 0, pokud soubor $file neexistuje v slozce rc souboru
                file_put_contents($outPath.$this->getFileName($file).".out", ""); // TODO uncomment
                array_push($this->outFiles, $expectedFile);
                fprintf(STDERR, "Created new file: ".$expectedFile."\n");
            }
        }

    }

    /*
     * Vraci nazev souboru
     */
    private function getFileName($pathToFile)
    {
        return preg_replace('/^(.*\/)?(.+)\.(in|out|rc|src)$/','\2', $pathToFile);
    }

    /*
     * Vraci cestu ke slozce, ve ktere se nachazi dany soubor
     */
    private function getDirectoryPath($pathToFile)
    {
        return preg_replace('/^(.*\/).+\.(in|out|rc|src)$/','\1', $pathToFile);
    }
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

    public function __construct()
    {
        $this->recursive = false;
//        $this->directory = './';
        $this->directory = getcwd().'/'; // pwd
        $this->parseScript = 'parse.php';
        $this->intScript = 'interpret.py';
    }

    /*
     * Validace argumentu scriptu
     */
    function checkArguments()
    {
        global $argc;
        global $argv;

        $validArgs = 0;

        $errorMsg = "Not allowed arguments!\n";

        $opts = getopt("", ["help", "directory:", "recursive", "parse-script:", "int-script:"]);

        if ($argc == 1)
        { // zadny argument
            return; // OK
        }
        else if ($argc >= 2 && $argc <= 6)
        { // jeden az sest argumentu
            if (array_key_exists('help', $opts))
            {
                fprintf(STDERR, "HELP TEXT TODO\n"); // TODO
                exit(0);
            }
            if (array_key_exists('recursive', $opts))
            {
                $this->recursive = true;
                $validArgs++;
            }
            if (array_key_exists('directory', $opts))
            {
                if (substr($opts['directory'], -1) != '/') // pokud posledni znak neni /, pridat / nakonec
                    $opts['directory'] = $opts['directory']."/";
                $this->directory = $opts['directory'];
                $validArgs++;
            }
            if (array_key_exists('parse-script', $opts))
            {
                $this->parseScript = $opts['parse-script'];
                $validArgs++;
            }
            if (array_key_exists('int-script', $opts))
            {
                $this->intScript = $opts['int-script'];
                $validArgs++;
            }
            if (!($validArgs == (count($argv) - 1)))
            { // pokud byly zadany dalsi nechtene arguemnty: chyba
                fprintf(STDERR, $errorMsg);
                exit(10); // TODO ERROR CODE???
            }
        }
        else
        { // nespravny pocet argumentu
            fprintf(STDERR, "Bad arguments count!\n");
            exit(10);
        }

        if (!file_exists($this->parseScript))
        { // kontrola existence souboru parse.php
            fprintf(STDERR, "File does not exist: ".$this->parseScript."\n");
            exit(10);
        }
        if (!file_exists($this->intScript))
        { // kontrola existence souboru interpret.py
            fprintf(STDERR, "File does not exist: ".$this->intScript."\n");
            exit(10);
        }
        if (!file_exists($this->directory))
        { // kontrola existence slozky
            fprintf(STDERR, "Directory does not exist: ".$this->directory."\n");
            exit(10);
        }
    }
}