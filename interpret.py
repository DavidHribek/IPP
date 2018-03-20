# File:     interpret.py
# Author:   David Hříbek (xhribe02)
# Date:     19.3.2018
# Desc:     Interpret jazyka ippcode18
#
from interpret_modules.errorHandler import ErrorHandler
from interpret_modules.xmlParser import XmlParser
from interpret_modules.argChecker import ArgChecker
from interpret_modules.instructionList import InstructionList

def Main():
    errorHandler = ErrorHandler() # zajistuje chybove ukonceni programu
    argChecker = ArgChecker(errorHandler)
    argChecker.check() # kontrola argumentu programu
    instList = InstructionList() # seznam instrukci k interpretaci

    xmlParser = XmlParser(argChecker.get_file_path(), errorHandler, instList)
    xmlParser.parse() # kontrola vstupniho XML

    for x in instList:
        try:
            print(x.opcode, x.arg1)
        except Exception:
            pass




if __name__ == '__main__':
    Main()