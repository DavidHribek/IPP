# File:     interpret.py
# Author:   David Hříbek (xhribe02)
# Date:     19.3.2018
# Desc:     Interpret jazyka ippcode18
#
from interpret_modules.errorHandler import ErrorHandler, print_to_stderr
from interpret_modules.xmlParser import XmlParser
from interpret_modules.argChecker import ArgChecker
from interpret_modules.instructionList import InstructionList
from interpret_modules.dataStack import DataStack

def Main():
    argChecker = ArgChecker()
    argChecker.check()                                  # kontrola argumentu programu

    instList = InstructionList()                        # instrukcni list
    xmlParser = XmlParser(argChecker.get_file_path(), instList)
    xmlParser.parse()                                   # kontrola vstupniho XML; nahrani instrukci do instList
    dataStack = DataStack()                             # datovy zasobnik

    while True:
        curr_inst = instList.get_next_instruction() # nacteni dalsi instrukce
        if curr_inst is None:
            # zajisteni ukonceni cyklu
            break
        # interpretace jednotlivych instrukci

        # BREAK
        if curr_inst.opcode == 'BREAK':
            print_to_stderr('Pozice v kodu: {}'.format(instList.get_instruction_counter()))
            # TODO pocet vykonanych instrukci
            # TODO obsah ramcu
        # PUSHS
        if curr_inst.opcode == 'PUSHS':
            # TODO
            dataStack.pushValue('ahoj')
        # POPS
        if curr_inst.opcode == 'POPS':
            # TODO
            dataStack.popValue()
            print(len(dataStack.stack))





if __name__ == '__main__':
    Main()

