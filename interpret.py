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
from interpret_modules.frameHandler import FrameHandler
from interpret_modules.errorHandler import ErrorHandler


def Main():
    errorHandler = ErrorHandler()                       # spravuje chybove stavy
    argChecker = ArgChecker()
    argChecker.check()                                  # kontrola argumentu programu

    instList = InstructionList()                        # instrukcni list
    xmlParser = XmlParser(argChecker.get_file_path(), instList)
    xmlParser.parse()                                   # kontrola vstupniho XML; nahrani instrukci do instList
    dataStack = DataStack()                             # datovy zasobnik
    frameHandler = FrameHandler()                       # stara se o GF, LF, TF


    # frameStack = FrameStack()                           # zasobnik ramcu
    # tmpFrame = TemporaryFrame(frameStack)               # docasny ramec (temporary frame)
    # frameStack.set_temporary_frame(tmpFrame)            # predani TF do zasobniku ramcu (pro pozdejsi komunikaci)

    while True:
        curr_inst = instList.get_next_instruction() # nacteni dalsi instrukce
        if curr_inst is None:
            # zajisteni ukonceni cyklu
            break

        # interpretace jednotlivych instrukci
        # BREAK
        if curr_inst.opcode == 'BREAK':
            print_to_stderr('Pozice v kodu:                  {}'.format(instList.get_instruction_counter()))
            print_to_stderr('Pocet vykonanych instrukci:     {}'.format(instList.get_instruction_done_number()))
            print_to_stderr('Zasobnik ramcu:                 {} (Celkem: {})'.format(frameHandler.get_frame_stack(), len(frameHandler.get_frame_stack())))
            # LOKALNI RAMEC
            if frameHandler.get_frame('LF') == 'NEDEFINOVAN':
                print_to_stderr('Lokalni ramec (LF):             {}'.format(frameHandler.get_frame('LF')))
            else:
                print_to_stderr('Lokalni ramec (LF):             {} (Celkem: {})'.format(frameHandler.get_frame('LF'), len(frameHandler.get_frame('LF'))))
            # DOCASNY RAMEC
            if frameHandler.get_frame('TF') == 'NEDEFINOVAN':
                print_to_stderr('Docasny ramec (TF):             {}'.format(frameHandler.get_frame('TF')))
            else:
                print_to_stderr('Docasny ramec (TF):             {} (Celkem: {})'.format(frameHandler.get_frame('TF'), len(frameHandler.get_frame('TF'))))
            # GLOBALNI RAMEC
            print_to_stderr('Globalni ramec (GF):            {} (Celkem: {})'.format(frameHandler.get_frame('GF'), len(frameHandler.get_frame('GF'))))

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
            frameHandler.create_tmp_frame()
        # PUSHFRAME
        elif curr_inst.opcode == 'PUSHFRAME':
            frameHandler.push_tmp_frame_to_frame_stack()
        # POPFRAME
        elif curr_inst.opcode == 'POPFRAME':
            frameHandler.pop_frame_stack_to_temporary_frame()
        # DEFVAR
        elif curr_inst.opcode == 'DEFVAR':
            frameHandler.defvar(curr_inst.arg1)
        # WRITE
        elif curr_inst.opcode == 'WRITE':
            type, value = frameHandler.get_arg_type_and_value(curr_inst.arg1)
            if value is None:
                # promenna nebyla inicializovana
                errorHandler.exit_with_error(56, 'CHYBA: Pokus o cteni neinicializovane promenne ({})'.format(curr_inst.arg1['text']))
            print(bytes(value, 'utf-8').decode('unicode_escape')) # TODO escape
        # MOVE
        elif curr_inst.opcode == 'MOVE':
            type, value = frameHandler.get_arg_type_and_value(curr_inst.arg2)
            frameHandler.set_var(curr_inst.arg1, type, value)




if __name__ == '__main__':
    Main()

