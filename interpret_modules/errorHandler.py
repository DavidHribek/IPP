# File:     errorHandler.py
# Author:   David Hříbek (xhribe02)
# Date:     19.3.2018
# Desc:     Interpret jazyka ippcode18
#
import sys

def print_to_stderr(msg, *args):
    """Vypise zpravu na stderr"""
    print(msg, file=sys.stderr)

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

    def exit_with_error(self, error_number, msg=None):
        """Vypise chybovou hlasku a ukonci skript"""
        if msg:
            # explicitni chybova zprava
            print(msg, file=sys.stderr)
        else:
            # vychozi chybova zprava
            print(self.erros[error_number], file=sys.stderr)
        exit(error_number)
