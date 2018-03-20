# File:     argChecker.py
# Author:   David Hříbek (xhribe02)
# Date:     19.3.2018
# Desc:     Interpret jazyka ippcode18
#
import getopt
import sys
from interpret_modules.errorHandler import ErrorHandler

class ArgChecker(ErrorHandler):
    def __init__(self):
        self.source = ''

    def get_file_path(self):
        return self.source

    def check(self):
        """Kontrola argumentu skriptu"""
        try:
            opts, args = getopt.getopt(sys.argv[1:], "", ['help', 'source='])
        except getopt.GetoptError as error:
            # nepovolena kombinace argumentu
            self.exit_with_error(10)
        # pocet argumentu musi byt 1
        if len(opts) != 1:
            self.exit_with_error(10)
        # vyhodnoceni argumentu
        opts = opts[0]
        o, v = opts
        if o == '--help':
            print('HELP TEXT') # TODO
        elif o == '--source':
            self.source = v
        else:
            # Zde se program pravdepodobne nedostane
            self.exit_with_error(10)




        # arg_parser = argparse.ArgumentParser(description='Interpret XML reprezentace kódu IPPcode18')
        # arg_parser.add_argument('help', help='Vypíše informace o skriptu')
        # arg_parser.add_argument('--source', type=str, help='Vstupní soubor s XML reprezentací zdrojového kódu IPPcode18',
        #                         required=True)
        # args = arg_parser.parse_args()
        # print(args.source)
        # if (args.source == ''):
        #     self.error_handler.exit_with_error(10)

