import sys
class ErrorHandler():
    def __init__(self):
        self.erros = {
            10: 'Chybějící parametr skriptu (je-li třeba) nebo použití zakázané kombinace parametrů',
            11: 'Chyba při otevírání vstupních souborů (např. neexistence, nedostatečné oprávnění)',
            12: 'Chyba při otevření výstupních souborů pro zápis (např. nedostatečné oprávnění)',
            99: 'Interní chyba (neovlivněná vstupními soubory či parametry příkazové řádky; např. chyba alokace paměti)',

            31: 'Chybný XML formát ve vstupním souboru (soubor není tzv. dobře formátovaný, angl. well-formed nebo nemá očekávanou strukturu)',
            32: 'Chyba lexikální nebo syntaktické analýzy textových elementů a atributů ve vstupním XML souboru (např. chybný lexém pro řetězcový literál, neznámý operační kód apod.).'
        }

    def exit_with_error(self, error_number):
        """Vypise chybovou hlasku a ukonci skript"""
        print(self.erros[error_number], file=sys.stderr)
        exit(error_number)
