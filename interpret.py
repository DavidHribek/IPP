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
from interpret_modules.frameStack import FrameStack
from interpret_modules.temporaryFrame import TemporaryFrame

def Main():
    argChecker = ArgChecker()
    argChecker.check()                                  # kontrola argumentu programu

    instList = InstructionList()                        # instrukcni list
    xmlParser = XmlParser(argChecker.get_file_path(), instList)
    xmlParser.parse()                                   # kontrola vstupniho XML; nahrani instrukci do instList
    dataStack = DataStack()                             # datovy zasobnik
    frameStack = FrameStack()                           # zasobnik ramcu
    tmpFrame = TemporaryFrame(frameStack)               # docasny ramec (temporary frame)
    frameStack.set_temporary_frame(tmpFrame)            # predani TF do zasobniku ramcu (pro pozdejsi komunikaci)

    while True:
        curr_inst = instList.get_next_instruction() # nacteni dalsi instrukce
        if curr_inst is None:
            # zajisteni ukonceni cyklu
            break

        # interpretace jednotlivych instrukci
        # BREAK
        if curr_inst.opcode == 'BREAK':
            print_to_stderr('Pozice v kodu:                 {}'.format(instList.get_instruction_counter()))
            print_to_stderr('Pocet vykonanych instrukci:    {}'.format(instList.get_instruction_done_number()))
            print_to_stderr('Zasobnik ramcu:                {} (Celkem: {})'.format(frameStack.get_frame_stack(), len(frameStack.get_frame_stack())))
            print_to_stderr('Docasny ramec (TF):            {}'.format(tmpFrame.get_frame()))
            print_to_stderr('Lokalni ramec (LF):            {}'.format(frameStack.get_local_frame()))
        # PUSHS
        elif curr_inst.opcode == 'PUSHS':
            # TODO
            dataStack.pushValue('ahoj')
        # POPS
        elif curr_inst.opcode == 'POPS':
            # TODO
            dataStack.popValue()
            print(len(dataStack.stack))
        # CREATEFRAME
        elif curr_inst.opcode == 'CREATEFRAME':
            tmpFrame.create_new_frame()
        # PUSHFRAME
        elif curr_inst.opcode == 'PUSHFRAME':
            tmpFrame.push_frame_to_frame_stack()
        elif curr_inst.opcode == 'POPFRAME':
            frameStack.pop_frame_to_temporary_frame()





if __name__ == '__main__':
    Main()

