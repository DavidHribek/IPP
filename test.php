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
$HtmlGenerator = new HtmlGenerator($DirectoryScanner);


$TmpFile->create();
foreach ($DirectoryScanner->directories as $dir)
{
    foreach ($DirectoryScanner->testFiles[$dir] as $test)
    {
        $srcFile = $dir.$test['name'].'.src';
        $rcFile = $dir.$test['name'].'.rc';
        $inFile = $dir.$test['name'].'.in';
        $outFile = $dir.$test['name'].'.out';

        $srcFileName = $test['name'] . '.src';
        $rcFileName = $test['name'] . '.rc';
        $inFileName = $test['name'] . '.in';
        $outFileName = $test['name'] . '.out';

//        fprintf(STDERR, "\n\nFIL NAME: " . $srcFile . "\n"); // DEBUG
        unset($parseOutput);
        exec('php5.6 ' . $Arguments->parseScript . ' < ' . $srcFile, $parseOutput, $parseReturnCode); // parse.php < soubor.src
        if ($parseReturnCode == 0) // nasleduje interpretace
        {
            $TmpFile->writeExecOutput($parseOutput); // naplni tmp soubor vystupem z parseru
            unset($interpretOutput);
            exec('python3.6 ' . $Arguments->intScript . ' --source=' . $TmpFile->getPath() . ' < ' . $inFile, $interpretOutput, $interpretReturnCode); // interpret.py < XML
            $TmpFile->reset();
            $TmpFile->writeExecOutput($interpretOutput); // naplni tmp soubor vystupem z interpretu
//            fprintf(STDERR, "INT OUTP: |" . $TmpFile->getAsString() . "|\n"); // DEBUG
//            fprintf(STDERR, "REF OUTP: |" . file_get_contents($outFile) . "|\n"); // DEBUG
            if ($interpretReturnCode == file_get_contents($rcFile)) // ocekavany navratovy kod
            {
                if ($interpretReturnCode == 0) // porovnani vystupu interpretu s referencnim vystupem
                {
                    exec('diff ' . $TmpFile->getPath() . ' ' . $outFile, $output /*dale nepouzito*/, $diffReturnCode); // porovnani vystupu interpretu a .out soboru
                    if ($diffReturnCode == 0) // vystup interpretu == .out soubor
                    {
                        $DirectoryScanner->addTestResult($dir, $test['name'], 0, 0, true);
                    } else {
                        $DirectoryScanner->addTestResult($dir, $test['name'], 0, 0, false);
                    }
                } else {
                    $DirectoryScanner->addTestResult($dir, $test['name'], 0, $interpretReturnCode, true);
                }
            } else // neocekavany navratovy kod
            {
                $DirectoryScanner->addTestResult($dir, $test['name'], 0, $interpretReturnCode, false);
            }
        } else // chyba parsovani
        {
            if ($parseReturnCode == file_get_contents($rcFile)) // ocekavany navratovy kod
            {
                $DirectoryScanner->addTestResult($dir, $test['name'], $parseReturnCode, '', true);
            } else // neocekavany navratovy kod
            {
                $DirectoryScanner->addTestResult($dir, $test['name'], $parseReturnCode, '', false);
            }
        }
    }
}

$TmpFile->close();
$HtmlGenerator->generate();
/*--------------------------------------------------TRIDY/FUNKCE------------------------------------------------------*/

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
            $fileName = $this->getFileName($r[0]); // nazev souboru bez pripony
            $dir = $this->getDirectoryPath(realpath($r[0])); // cesta ke slozce souboru
            if (!in_array($dir, $this->directories)) // zapamatovani unikatni slozky
                $this->directories[] = $dir;

            $this->testFiles[$dir][$fileName]['name'] = $fileName;
            if (!file_exists($dir.$fileName.'.rc'))
                $this->generateFile($dir, $fileName.'.rc', "0");
            if (!file_exists($dir.$fileName.'.in'))
                $this->generateFile($dir, $fileName.'.in', "");
            if (!file_exists($dir.$fileName.'.out'))
                $this->generateFile($dir, $fileName.'.out', "");
        }

        array_multisort($this->testFiles, SORT_ASC);
        sort($this->directories); // serazeni slozek
//        foreach ($this->testFiles as &$dir) // serazeni souboru ve slozkach
//            sort($dir);
    }

    /*
     * Zapise vysledek testu do promenne $testFiles
     */
    public function addTestResult($dir, $fileName, $parseReturnCode, $interpretReturnCode, $pass)
    {
        $this->testFiles[$dir][$fileName]['parseReturnCode'] = $parseReturnCode;
        $this->testFiles[$dir][$fileName]['interpretReturnCode'] = $interpretReturnCode;
        $this->testFiles[$dir][$fileName]['pass'] = $pass;
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
        return preg_replace('/^(.*\/)?(.+)\.src$/','\2', $pathToFile);
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

/*
 * Trida pro vygenerovani HTML vystupu do STDOUT
 */
class HtmlGenerator {
    private $DirectoryScanner;

    public function __construct($DirectoryScanner)
    {
        $this->DirectoryScanner = $DirectoryScanner;
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
                    background: #3686d0;
                    color: white;            
                }
                table {
                    width: 100%;
                    -webkit-box-shadow: 1px 1px 5px 0px rgba(0,0,0,0.47);
                    -moz-box-shadow: 1px 1px 5px 0px rgba(0,0,0,0.47);
                    box-shadow: 1px 1px 5px 0px rgba(0,0,0,0.47);
                    font-family: Helvetica, Arial, Helvetica, sans-serif;
                    border-collapse: collapse;
                }
                
                table td, table th {
                    padding: 8px;
                }
                
                table tbody tr {
                    //border: 2px solid white;
                }
                
                table tr:nth-child(even){background-color: #f2f2f2;}
                
                table tr:hover {background-color: #ddd;}
                
                table th {
                    padding-top: 12px;
                    padding-bottom: 12px;
                    text-align: left;
                    background-color: #3686d0;
                    color: white;
                    text-align: center;
                }
                .dir-heading {
                    text-align: left;
                    padding: 5px 15px;    
                    background-color: #54616d !important;  
                    color: white;;          
                }
                .background-gray{
                    background: #dcdcd9;
                }
                .failed {
                    color: #e03d3d;
                }
                .passed {
                    color: #00b700;
                }
                .center {
                    text-align: center;
                }
                .left {
                    text-align: left;
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
                <pre class="center">
$$$$$$\ $$$$$$$\  $$$$$$$\                            $$\             $$\   $$$$$$\  
\_$$  _|$$  __$$\ $$  __$$\                           $$ |          $$$$ | $$  __$$\ 
  $$ |  $$ |  $$ |$$ |  $$ | $$$$$$$\  $$$$$$\   $$$$$$$ | $$$$$$\  \_$$ | $$ /  $$ |
  $$ |  $$$$$$$  |$$$$$$$  |$$  _____|$$  __$$\ $$  __$$ |$$  __$$\   $$ |  $$$$$$  |
  $$ |  $$  ____/ $$  ____/ $$ /      $$ /  $$ |$$ /  $$ |$$$$$$$$ |  $$ | $$  __$$< 
  $$ |  $$ |      $$ |      $$ |      $$ |  $$ |$$ |  $$ |$$   ____|  $$ | $$ /  $$ |
$$$$$$\ $$ |      $$ |      \$$$$$$$\ \$$$$$$  |\$$$$$$$ |\$$$$$$$\ $$$$$$\\$$$$$$  |
\______|\__|      \__|       \_______| \______/  \_______| \_______|\______|\______/ 
                </pre>
                <table class="center">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Source files</th>
                            <th>Parse</th>
                            <th>Interpret</th>
                            <th>Expected</th>
                            <th>RC</th>
                            <th>Result</th>
                        </tr>
                    </thead>';
//                    <tbody>';

        foreach ($this->DirectoryScanner->directories as $dir) {
            $testDirCount = 0;
            $testDirPassedCount = 0;
            $testDirCodesPassedCount = 0;
            $html = $html.'<tbody>';

            foreach ($this->DirectoryScanner->testFiles[$dir] as $test) {
                $testDirCount++;

                $rcFile = $dir.$test['name'].'.rc';
                $srcFileName = $test['name'] . '.src';

                $html = $html."<tr>";
                // ROW number
                $testCount++;
//                $testCount = "";
                $html = $html."<td class='center'>".$testCount."</td>\n";
                // ROW src file
                $html = $html."<td>".$srcFileName."</td>\n";
                // ROW other files
//                $html = $html . "<td>".$inFileName."</br>".$outFileName."</br>".$rcFileName."</td>\n";
                // ROW return code
                $html = $html . "<td class='center'>" . $test['parseReturnCode'] . "</td>\n";
                $html = $html . "<td class='center'>" . $test['interpretReturnCode'] . "</td>\n";
                $html = $html . "<td class='center'>" . file_get_contents($rcFile) . "</td>\n";
                // ROW return code ok/fail
                if ((file_get_contents($rcFile) == $test['interpretReturnCode']) || (($test['interpretReturnCode'] == "") && (file_get_contents($rcFile) == $test['parseReturnCode']))) {
                    $html = $html . "<td class='passed'>&#10004;</td>\n";
                    $testCodesPassedCount++;
                    $testDirCodesPassedCount++;
                } else
                    $html = $html . "<td class='failed'>&#10007;</td>";
                // ROW ok/fail
                if ($test['pass']) {
                    $html = $html . "<td class='passed'>&#10004;</td>\n";
                    $testPassedCount++;
                    $testDirPassedCount++;
                } else
                    $html = $html . "<td class='failed'>&#10007;</td>";
                $html = $html . "</tr>\n";
            }
            $html = $html.'
                <tr class="dir-heading">
                    <td colspan="5" class="left">'.$dir.' &#8599;</td>
                    <td class="center">'.$testDirCodesPassedCount.'/'.$testDirCount.'</td>
                    <td class="center">'.$testDirPassedCount.'/'.$testDirCount.'</td>
                </tr>
            </tbody>';
        }

        $html = $html.
                    '<tr id="summary">
                        <td class="center" colspan="1">Summary</td>
                        <td class="center" colspan="4"></td>
                        <td class="center">'.$testCodesPassedCount.'/'.$testCount.'</td>
                        <td class="center">'.$testPassedCount.'/'.$testCount.'</td>
                    </tr>
                </tbody>
            </table>
                <ul>
                    <li class="passed">&#10004; PASSED</li>
                    <li class="failed">&#10007; FAILED</li>
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