<?php
/**
 * Název souboru: test.php
 * Popis: Projekt 1 do předmětu IPP 2018/2019 (testy), FIT VUT
 * Vytvořil: David Hříbek
 * Datum: 8.2.18
 */

$Arguments = new Arguments();
$Arguments->checkArguments();

$DirectoryScanner = new DirectoryScanner();
$DirectoryScanner->scan($Arguments->directory, $Arguments->recursive);
$TmpFile = new TemporaryFile();
$HtmlGenerator = new HtmlGenerator();


$TmpFile->create();
foreach ($DirectoryScanner->directories as $dir) {
    foreach ($DirectoryScanner->testFiles[$dir] as $fileName)
    {
        $srcFile = $dir.$fileName.'.src';
        $rcFile = $dir.$fileName.'.rc';
        $inFile = $dir.$fileName.'.in';
        $outFile = $dir.$fileName.'.out';

        $srcFileName = $fileName.'.src';
        $rcFileName = $fileName.'.rc';
        $inFileName = $fileName.'.in';
        $outFileName = $fileName.'.out';


        fprintf(STDERR, "\n\nFIL NAME: " . $srcFile . "\n"); // DEBUG
        unset($parseOutput);
        exec('php5.6 ' . $Arguments->parseScript . ' < ' . $srcFile, $parseOutput, $parseReturnCode); // parse.php < soubor.src
        if ($parseReturnCode == 0) // nasleduje interpretace
        {
            $TmpFile->writeExecOutput($parseOutput); // naplni tmp soubor vystupem z parseru
            unset($interpretOutput);
            exec('python3.6 ' . $Arguments->intScript . ' --source=' . $TmpFile->getPath() . ' < ' . $inFile, $interpretOutput, $interpretReturnCode); // interpret.py < XML
            $TmpFile->reset();
            $TmpFile->writeExecOutput($interpretOutput); // naplni tmp soubor vystupem z interpretu
            fprintf(STDERR, "INT OUTP: |".$TmpFile->getAsString()."|\n"); // DEBUG
            fprintf(STDERR, "REF OUTP: |".file_get_contents($outFile)."|\n"); // DEBUG
            if ($interpretReturnCode == file_get_contents($rcFile)) // ocekavany navratovy kod
            {
                if ($interpretReturnCode == 0) // porovnani vystupu interpretu s referencnim vystupem
                {
                    exec('diff ' . $TmpFile->getPath() . ' ' . $outFile, $output /*dale nepouzito*/, $diffReturnCode); // porovnani vystupu interpretu a .out soboru
                    if ($diffReturnCode == 0) // vystup interpretu == .out soubor
                    {
                        $HtmlGenerator->addTestResult($srcFile, $inFile, $outFile, $rcFile, true, 0, 0);
                    } else {
                        $HtmlGenerator->addTestResult($srcFile, $inFile, $outFile, $rcFile, false, 0, 0);
                    }
                } else {
                    $HtmlGenerator->addTestResult($srcFile, $inFile, $outFile, $rcFile, true, 0, $interpretReturnCode);
                }
            } else // neocekavany navratovy kod
            {
                $HtmlGenerator->addTestResult($srcFile, $inFile, $outFile, $rcFile, false, 0, $interpretReturnCode);
            }
        //        if ($interpretReturnCode == 0)
    //        {
    //            exec('diff '.$TmpFile->getPath().' '.$outFile, $output /*dale nepouzito*/, $diffReturnCode); // porovnani vystupu interpretu a .out soboru
    //            if ($diffReturnCode == 0) // vystup interpretu == .out soubor
    //            {
    //                $HtmlGenerator->addTestResult($srcFile, $inFile, $outFile, $rcFile, true, 0, 0, 'correct');
    //            }
    //            else
    //            {
    //                $HtmlGenerator->addTestResult($srcFile, $inFile, $outFile, $rcFile, false, 0, 0, 'incorrect');
    //            }
    //        }
    //        else // chyba interpretace
    //        {
    //            if ($interpretReturnCode == file_get_contents($rcFile)) // chybove kody se shoduji
    //            {
    //                $HtmlGenerator->addTestResult($srcFile, $inFile, $outFile, $rcFile, true, 0, $interpretReturnCode, 'notset');
    //            }
    //            else // chybove kody se neshoduji
    //            {
    //                $HtmlGenerator->addTestResult($srcFile, $inFile, $outFile, $rcFile, false, 0, $interpretReturnCode, 'notset');
    //            }
    //        }
        } else // chyba parsovani
        {
            if ($parseReturnCode == file_get_contents($rcFile)) // ocekavany navratovy kod
            {
                $HtmlGenerator->addTestResult($srcFile, $inFile, $outFile, $rcFile, true, $parseReturnCode, '');
            } else // neocekavany navratovy kod
            {
                $HtmlGenerator->addTestResult($srcFile, $inFile, $outFile, $rcFile, false, $parseReturnCode);
            }
        }
    }
}

$TmpFile->close();
$HtmlGenerator->generate();
/*--------------------------------------------------TRIDY/FUNKCE------------------------------------------------------*/
/*
 * Trida pro vygenerovani HTML vystupu do STDOUT
 */
class HtmlGenerator {
    private $testResults;

    public function __construct()
    {
        $this->testResults = [];
    }

    /*
     * Vlozi vysledek testu do promenne $testResult
     */
    public function addTestResult($srcFile, $infile, $outFile, $rcFile, $pass, $parseReturnCode, $interpretReturnCode)
    {
        $this->testResults[] = [
            'srcFile' => $srcFile,
            'inFile' => $infile,
            'outFile' => $outFile,
            'rcFile' => $rcFile,
            'parseReturnCode' => $parseReturnCode,
            'interpretReturnCode' => $interpretReturnCode,
            'pass' => $pass
        ];
    }

    /*
     * Vygeneruje html stranku na STDOUT
     */
    public function generate()
    {
        $testCount = 0;
        $testPassedCount = 0;
        $testCodesPassedCount = 0;


        $html =
        '<!doctype html>
        <html lang=\"cz\">
        <head>
            <meta charset=\"utf-8\">
            <title>IPPcode18 Test</title>
            <meta name=\"Přehled testování scriptů parse.php a interpret.py\">
            <meta name=\"David Hříbek\">
            
            <style>
                h1 {
                    text-align: center;
                    color: #676d6a;
                }
                #main {
                    width: 70%;
                    margin: auto;
                }
                tr#summary{
                    background: #676d6a;
                    color: white;            
                }
                table {
                    -webkit-box-shadow: 1px 1px 5px 0px rgba(0,0,0,0.47);
                    -moz-box-shadow: 1px 1px 5px 0px rgba(0,0,0,0.47);
                    box-shadow: 1px 1px 5px 0px rgba(0,0,0,0.47);
                    font-family: Helvetica, Arial, Helvetica, sans-serif;
                    border-collapse: collapse;
                }
                
                table td, table th {
                    padding: 8px;
                }
                
                table tr:nth-child(even){background-color: #f2f2f2;}
                
                table tr:hover {background-color: #ddd;}
                
                table th {
                    padding-top: 12px;
                    padding-bottom: 12px;
                    text-align: left;
                    background-color: #676d6a;
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
                .background-gray{
                    background: #dcdcd9;
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
                
                ul li {
                    display: inline;
                    float: left;
                    padding: 0 15px;
                }
                ul li div {
                    float: left;
                    margin-right: 10px !important;
                }
            </style>
        </head>

        <body>
            <div id="main">
                <h1>IPPcode18</h1>
                <table>
                    <thead>
                        <tr>
                            <th rowspan="2">No.</th>
                            <th rowspan="2">Source files (IPPcode18)</th>
                            <th rowspan="2">Other files</th>
                            <th colspan="4">Return code</th>
                            <th rowspan="2">Result</th>
                        </tr>
                        <tr>
                            <th>Parse</th>
                            <th>Interpret</th>
                            <th>Expected</th>
                            <th>Passed</th>  
                        </tr>
                    </thead>
                    <tbody>';

        foreach ($this->testResults as $testResult)
        {
            $html = $html."<tr>";
            // ROW number
            $testCount++;
            $html = $html."<td class='center'>".$testCount."</td>\n";
            // ROW src file
            $html = $html."<td>".$testResult['srcFile']."</td>\n";
            // ROW other files
            $html = $html."<td>".$testResult['inFile']."</br>".$testResult['outFile']."</br>".$testResult['rcFile']."</td>\n";
            // ROW return code
            $html = $html."<td class='center'>".$testResult['parseReturnCode']."</td>\n";
            $html = $html."<td class='center'>".$testResult['interpretReturnCode']."</td>\n";
            $html = $html."<td class='center'>".file_get_contents($testResult['rcFile'])."</td>\n";
            // ROW return code ok/fail
            if ((file_get_contents($testResult['rcFile']) == $testResult['interpretReturnCode']) || (($testResult['interpretReturnCode'] == "") && (file_get_contents($testResult['rcFile']) == $testResult['parseReturnCode']))) {
                $html = $html . "<td><div id='circle' class='passed'></div></td>\n";
                $testCodesPassedCount++;
            }
            else
                $html = $html."<td><div id='circle' class='failed'></div></td>";
            // ROW ok/fail
            if ($testResult['pass']) {
                $html = $html . "<td class='background-gray'><div id='circle' class='passed'></div></td>\n";
                $testPassedCount++;
            }
            else
                $html = $html."<td class='background-gray'><div id='circle' class='failed'></div></td>";
            $html = $html."</tr>\n";
        }

        $html = $html.
                    '<tr id="summary">
                        <td colspan="3">Summary</td>
                        <td colspan="3"></td>
                        <td class="center">'.$testCodesPassedCount.'/'.$testCount.'</td>
                        <td class="center">'.$testPassedCount.'/'.$testCount.'</td>
                    </tr>
                </tbody>
            </table>
                <ul>
                    <li>PASSED<div id=\'circle\' class=\'passed\'></div></li>
                    <li>FAILED<div id=\'circle\' class=\'failed\'></div></li>
                </ul>
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
    public $directories;
    public $testFiles;

    public function __construct()
    {
        $this->directories = [];
        $this->testFiles = [];
    }

    /*
     * Naskenuje danou slozku, ulozi cesty k .src .in .out .rc souborum do promennych, vygeneruje prazdne soubory (pokud je treba)
     */
    public function scan($dir, $recursive)
    {
        $Directory = new RecursiveDirectoryIterator($dir);
        if ($recursive) // pokud byl zadan argument --recursive
            $Iterator = new RecursiveIteratorIterator($Directory);
        else
            $Iterator = new IteratorIterator($Directory);

        $Regex = new RegexIterator($Iterator, '/^.+\.src$/i', RecursiveRegexIterator::GET_MATCH);
        foreach ($Regex as $r)
        {
            $name = $this->getFileName($r[0]); // nazev souboru bez pripony
            $dir = $this->getDirectoryPath($r[0]); // cesta ke slozce souboru
            if (!in_array($dir, $this->directories)) // zapamatovani unikatni slozky
                $this->directories[] = $dir;

            $this->testFiles[$dir][] = $name;
            if (!file_exists($dir.$name.'.rc'))
                $this->generateFile($dir, $name.'.rc', "0");
            if (!file_exists($dir.$name.'.in'))
                $this->generateFile($dir, $name.'.in', "");
            if (!file_exists($dir.$name.'.out'))
                $this->generateFile($dir, $name.'.out', "");
        }

        sort($this->directories); // serazeni slozek
        foreach ($this->testFiles as &$dir) // serazeni souboru ve slozkach
            sort($dir);
    }

    /*
     * Vygeneruje soubor
     */
    private function generateFile($directory, $fileName, $content)
    {
        file_put_contents($directory.$fileName, $content);
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