# IPP - Principy programovacích jazyků 2018/2019

## Zadání (více soubor ipp18spec.pdf)

* Navrhněte, implementujte, dokumentujte a testujte sadu skriptů pro **interpretaci nestrukturovaného
imperativního jazyka** IPPcode18.
* K implementaci vytvořte odpovídající stručnou programovou dokumentaci.
* Projekt se skládá ze dvou úloh a je individuální:
    * První úloha se skládá ze skriptu **parse.php** v jazyce PHP 5.6 (viz sekce 3).
    * Druhá úloha se skládá ze skriptu **interpret.py** v jazyce Python 3.6 (viz sekce 4), testovacího skriptu **test.php** v jazyce
    PHP 5.6 (viz sekce 5) a **dokumentace těchto skriptů** (viz sekce 2.1).

### Analyzátor kódu v IPPcode18 (parse.php)
Skript typu filtr (parse.php v jazyce PHP 5.6) **načte ze standardního vstupu zdrojový kód v IPPcode18** (viz sekce 6),
**zkontroluje lexikální a syntaktickou správnost kódu** a **vypíše na standardní
výstup XML reprezentaci programu** dle specifikace v sekci 3.1. 

#### Tento skript bude pracovat s těmito parametry:
* **--help** viz společný parametr všech skriptů v sekci 2.2

#### Chybové návratové kódy specifické pro analyzátor:
* **21** - lexikální nebo syntaktická chyba zdrojového kódu zapsaného v IPPcode18.


### Interpret XML reprezentace kódu (interpret.py)
Program **načte XML reprezentaci programu ze zadaného souboru a tento program s využitím standardního vstupu a výstupu interpretuje**. Vstupní XML reprezentace je např. generována skriptem
parse.php ze zdrojového kódu v IPPcode18. Interpret navíc oproti sekci 3.1 podporuje existenci vo-
litelných dokumentačních textových atributů name a description v kořenovém elementu program.
Sémantika jednotlivých instrukcí IPPcode18 je popsána v sekci 6.

#### Tento skript bude pracovat s těmito parametry:
* **--help** viz společný parametr všech skriptů v sekci 2.2
* **--source=file** vstupní soubor s XML reprezentací zdrojového kódu dle definice ze sekce 3.1
#### Chybové návratové kódy specifické pro interpret:
* **31** - chybný XML formát ve vstupním souboru (soubor není tzv. dobře formátovaný, angl. well-formed (viz [1]) nebo nemá očekávanou strukturu).
* **32** - chyba lexikální nebo syntaktické analýzy textových elementů a atributů ve vstupním XML souboru (např. chybný lexém pro řetězcový literál, neznámý operační kód apod.).

Chybové návratové kódy interpretu v případě chyby během interpretace jsou uvedeny v popisu jazyka IPPcode18 (viz sekce 6.1).

### Testovací rámec (test.php)
Skript (test.php v jazyce PHP 5.6) bude sloužit pro **automatické testování postupné aplikace
parse.php a interpret.py**. Skript projde zadaný adresář s testy a využije je pro automatické
otestování správné funkčnosti obou předchozích programů včetně vygenerování přehledného souhrnu
v HTML 5 do standardního výstupu. Testovací skript nemusí u předchozích dvou skriptů testovat
jejich dodatečnou funkčnost aktivovanou parametry příkazové řádky (s výjimkou potřeby parametru
--source).
#### Tento skript bude pracovat s těmito parametry:
* --help viz společný parametr všech skriptů v sekci 2.2
* --directory=path testy bude hledat v zadaném adresáři (chybí-li tento parametr, tak skript prochází aktuální adresář)
* --recursive testy bude hledat nejen v zadaném adresáři, ale i rekurzivně ve všech jeho podadresářích
* --parse-script=file soubor se skriptem v PHP 5.6 pro analýzu zdrojového kódu v IPPcode18 (chybí-li tento parametr, tak implicitní hodnotou je parse.php uložený v aktuálním adresáři)
* --int-script=file soubor se skriptem v Python 3.6 pro interpret XML reprezentace kódu v IPPcode18 (chybí-li tento parametr, tak implicitní hodnotou je interpret.py uložený v aktuálním adresáři)

#### Struktura testu
Každý test je tvořen až **4 soubory stejného jména s příponami src, in, out a rc** (ve stejném
adresáři). Soubor s příponou **src obsahuje zdrojový kód v jazyce IPPcode18**. Soubory s příponami
**in, out a rc obsahují vstup a očekávaný/referenční výstup interpretace a očekávaný první chybový
návratový kód analýzy a interpretace nebo bezchybový návratový kód 0**. Pokud soubor s příponou
in nebo out chybí, tak se automaticky dogeneruje prázdný soubor. V případě chybějícího souboru
s příponou rc se vygeneruje soubor obsahující návratovou hodnotu 0.
Testy budou umístěny v adresáři včetně případných podadresářů pro lepší kategorizaci testů.
Adresářová struktura může mít libovolné zanoření. Není třeba uvažovat symbolické odkazy apod.

## Příklad spuštění

## Dokumentace v souboru doc.pdf