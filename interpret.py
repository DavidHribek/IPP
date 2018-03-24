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
            print_to_stderr('Datovy zasobnik:                {} (Celkem: {})'.format(dataStack.get_stack(), len(dataStack.get_stack())))
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
            type, value = frameHandler.get_arg_type_and_value(curr_inst.arg1)
            dataStack.pushValue(type, value)
        # POPS
        elif curr_inst.opcode == 'POPS':
            type, value = dataStack.popValue()
            frameHandler.set_var(curr_inst.arg1, type, value)
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
        # WRITE, DPRINT
        elif curr_inst.opcode in  ['WRITE', 'DPRINT']:
            type, value = frameHandler.get_arg_type_and_value(curr_inst.arg1)
            if value is None:
                # promenna nebyla inicializovana
                errorHandler.exit_with_error(56, 'CHYBA: Pokus o cteni neinicializovane promenne ({})'.format(curr_inst.arg1['text']))
            if curr_inst.opcode == 'WRITE':
                # WRITE
                print(value)
                # print(bytes(value, 'utf-8').decode('unicode_escape')) # TODO escape
            else:
                # DPRINT
                print_to_stderr(value)
                # print_to_stderr(bytes(value, 'utf-8').decode('unicode_escape')) # TODO escape
        # MOVE
        elif curr_inst.opcode == 'MOVE':
            type, value = frameHandler.get_arg_type_and_value(curr_inst.arg2)
            frameHandler.set_var(curr_inst.arg1, type, value)
        # ADD, SUB, MUL, IDIV
        elif curr_inst.opcode in ['ADD', 'SUB', 'MUL', 'IDIV']:
            type1, value1 = frameHandler.get_arg_type_and_value(curr_inst.arg2)
            type2, value2 = frameHandler.get_arg_type_and_value(curr_inst.arg3)
            if type1 == type2 == 'int':
                if curr_inst.opcode == 'ADD':
                    # secteni
                    frameHandler.set_var(curr_inst.arg1, 'int', str(int(value1)+int(value2)))
                elif curr_inst.opcode == 'SUB':
                    # odecteni
                    frameHandler.set_var(curr_inst.arg1, 'int', str(int(value1) - int(value2)))
                elif curr_inst == 'MUL':
                    # vynasobeni
                    frameHandler.set_var(curr_inst.arg1, 'int', str(int(value1) * int(value2)))
                else:
                    # celociselne deleni
                    if int(value2) == 0:
                        # deleni nulou
                        errorHandler.exit_with_error(57, 'CHYBA: Deleni nulou (Cislo1: {}, Cislo2: {})'.format(value1, value2))
                    else:
                        frameHandler.set_var(curr_inst.arg1, 'int', str(int(value1) // int(value2)))
            else:
                errorHandler.exit_with_error(53, 'CHYBA: Spatne typy operandu instrukce ADD (Typ1: {}, Typ2: {})'.format(type1, type2))
        # TYPE
        elif curr_inst.opcode == 'TYPE':
            type, value = frameHandler.get_arg_type_and_value(curr_inst.arg2)
            if type is None:
                # pokud se jedna o neinicializovanou promennou, prepsat type na prazdny string
                type = ''
            # zapis typu arg2 do promenne arg1 jako string
            frameHandler.set_var(curr_inst.arg1, 'string', type)
        # CONCAT
        elif curr_inst.opcode == 'CONCAT':
            type1, value1 = frameHandler.get_arg_type_and_value(curr_inst.arg2)
            type2, value2 = frameHandler.get_arg_type_and_value(curr_inst.arg3)
            if type1 == type2 == 'string':
                # konkatenace retezcu a zapis do promene arg1
                frameHandler.set_var(curr_inst.arg1, 'string', value1+value2)
            else:
                # nepovolene typy operandu
                errorHandler.exit_with_error(53, 'CHYBA: Spatne typy operandu instrukce CONCAT (Typ1: {}, Typ2: {})'.format(type1, type2))
        # AND, OR
        elif curr_inst.opcode in ['AND', 'OR']:
            type1, value1 = frameHandler.get_arg_type_and_value(curr_inst.arg2)
            type2, value2 = frameHandler.get_arg_type_and_value(curr_inst.arg3)
            if type1 == type2 == 'bool':
                if curr_inst.opcode == 'AND':
                    # logicky soucin
                    log_and = 'true' if value1 == value2 == 'true' else 'false'
                    frameHandler.set_var(curr_inst.arg1, 'bool', log_and)
                else:
                    # logicky soucet
                    log_and = 'true' if 'true' in [value1, value2] else 'false'
                    frameHandler.set_var(curr_inst.arg1, 'bool', log_and)
            else:
                # nepovolene typy operandu
                errorHandler.exit_with_error(53, 'CHYBA: Spatne typy operandu instrukce {} (Typ1: {}, Typ2: {})'.format(curr_inst.opcode, type1, type2))
        # NOT
        elif curr_inst.opcode == 'NOT':
            type, value = frameHandler.get_arg_type_and_value(curr_inst.arg2)
            if type == 'bool':
                # logicka negace
                log_not = 'true' if value == 'false' else 'false'
                frameHandler.set_var(curr_inst.arg1, 'bool', log_not)
            else:
                errorHandler.exit_with_error(53, 'CHYBA: Spatny typ operandu instrukce NOT (Typ: {})'.format(type))

        # LABEL
        elif curr_inst.opcode == 'LABEL':
            # navesti jiz jsou v slovniku navesti
            continue
        # JUMP
        elif curr_inst.opcode == 'JUMP':
            instList.jump_to_label(curr_inst.arg1)
        # JUMPIFEQ
        elif curr_inst.opcode in ['JUMPIFEQ', 'JUMPIFNEQ']:
            type1, value1 = frameHandler.get_arg_type_and_value(curr_inst.arg2)
            type2, value2 = frameHandler.get_arg_type_and_value(curr_inst.arg3)
            if type1 == type2:
                # stejne typy
                if curr_inst.opcode == 'JUMPIFEQ' and value1 == value2:
                    # skok na navesti pri rovnosti
                    instList.jump_to_label(curr_inst.arg1)
                elif curr_inst.opcode == 'JUMPIFNEQ' and value1 != value2:
                    # skok na navesti pri nerovnosti
                    instList.jump_to_label(curr_inst.arg1)
                else:
                    # instrukce se ignoruje
                    pass
            else:
                errorHandler.exit_with_error(53, 'CHYBA: Argumenty instrukce {} nejsou stejneho typu (Typ1: {}, Typ2: {})'.format(curr_inst.opcode, type1, type2))
        # CALL
        elif curr_inst.opcode == 'CALL':
            instList.push_next_instruction_to_call_stack() # ulozi pozici nasledujici instrukce do zasobniku volani
            instList.jump_to_label(curr_inst.arg1)
        # RETURN
        elif curr_inst.opcode == 'RETURN':
            instList.pop_next_instruction_from_call_stack()
        # LT, GT, EQ
        elif curr_inst.opcode in ['LT', 'GT', 'EQ']:
            type1, value1 = frameHandler.get_arg_type_and_value(curr_inst.arg2)
            type2, value2 = frameHandler.get_arg_type_and_value(curr_inst.arg3)
            if type1 == type2:
                # typy jsou stejne
                if curr_inst.opcode == 'EQ':
                    # ekvivalence
                    is_equal = 'true' if value1 == value2 else 'false'
                    frameHandler.set_var(curr_inst.arg1, 'bool', is_equal)
                elif curr_inst.opcode == 'LT':
                    # mensi
                    if type1 == 'int':
                        # int
                        is_lesser = 'true' if int(value1) < int(value2) else 'false'
                        frameHandler.set_var(curr_inst.arg1, 'bool', is_lesser)
                    elif type1 == 'bool':
                        # bool
                        is_lesser = 'true' if value1 == 'false' and value2 == 'true' else 'false'
                        frameHandler.set_var(curr_inst.arg1, 'bool', is_lesser)
                    else:
                        #string
                        is_lesser = 'true' if value1 < value2 else 'false'
                        frameHandler.set_var(curr_inst.arg1, 'bool', is_lesser)
                else:
                    # vetsi
                    if type1 == 'int':
                        # int
                        is_greater = 'true' if int(value1) > int(value2) else 'false'
                        frameHandler.set_var(curr_inst.arg1, 'bool', is_greater)
                    elif type1 == 'bool':
                        # bool
                        is_greater = 'true' if value1 == 'true' and value2 == 'false' else 'false'
                        frameHandler.set_var(curr_inst.arg1, 'bool', is_greater)
                    else:
                        # string
                        is_greater = 'true' if value1 > value2 else 'false'
                        frameHandler.set_var(curr_inst.arg1, 'bool', is_greater)
            else:
                errorHandler.exit_with_error(53, 'CHYBA: Operandy instrukce {} nejsou stejneho typu (Typ1: {}, Typ2: {})'.format(curr_inst.opcode, type1, type2))
        # STRLEN
        elif curr_inst.opcode == 'STRLEN':
            type, value = frameHandler.get_arg_type_and_value(curr_inst.arg2)
            if type == 'string':
                # typ je spravny
                frameHandler.set_var(curr_inst.arg1, 'int', len(value))
            else:
                errorHandler.exit_with_error(53, 'CHYBA: Operand instrukce STRLEN neni typu string (Typ: {})'.format(type))
        # GETCHAR
        elif curr_inst.opcode == 'GETCHAR':
            type1, value1 = frameHandler.get_arg_type_and_value(curr_inst.arg2)
            type2, value2 = frameHandler.get_arg_type_and_value(curr_inst.arg3)
            if type1 == 'string' and type2 == 'int':
                # typy jsou spravne
                index = int(value2)
                if index >= 0 and index <= len(value1)-1:
                    # index je spravny, zapis znaku do promenne arg1
                    frameHandler.set_var(curr_inst.arg1, 'string', value1[index])
                else:
                    # indexace mimo retezec
                    errorHandler.exit_with_error(58, 'CHYBA: Indexace mimo retezec u instrukce GETCHAR')
            else:
                errorHandler.exit_with_error(53, 'CHYBA: Operandy instrukce GETCHAR nejsou typu string a int (Typ1: {}, Typ2: {})'.format(type1, type2))
        # SETCHAR
        elif curr_inst.opcode == 'SETCHAR':
            type1, value1 = frameHandler.get_arg_type_and_value(curr_inst.arg1)
            type2, value2 = frameHandler.get_arg_type_and_value(curr_inst.arg2)
            type3, value3 = frameHandler.get_arg_type_and_value(curr_inst.arg3)
            if type1 == 'string' and type2 == 'int' and type3 == 'string':
                # spravne typy
                index = int(value2)
                if value3 == '':
                    # prazdny retezec arg3
                    errorHandler.exit_with_error(58, 'CHYBA: Prazdny retezec s nahrazujicim znakem u instrukce SETCHAR')
                elif not (index >= 0 and index <= len(value1)-1):
                    # indexace mimo retezec promenne arg1
                    errorHandler.exit_with_error(58, 'CHYBA: Indexace mimo retezec u instrukce SETCHAR')
                else:
                    # nahrazeni znaku
                    value1 = list(value1)
                    value1[index] = value3[0]
                    value1 = ''.join(value1)
                    frameHandler.set_var(curr_inst.arg1, 'string', value1)
            else:
                errorHandler.exit_with_error(53, 'CHYBA: Spatne typy operandu instrukce SETCHAR (Typ1: {}, Typ2: {}, Typ3: {})'.format(type1, type2, type3))
        # INT2CHAR
        elif curr_inst.opcode == 'INT2CHAR':
            type, value = frameHandler.get_arg_type_and_value(curr_inst.arg2)
            if type == 'int':
                # spravne typy operandu
                try:
                    char = chr(int(value))
                except ValueError:
                    errorHandler.exit_with_error(58, 'CHYBA: Nevalidni ordinalni hodnota znaku v Unicode instrukce INT2CHAR (Hodnota: {})'.format(value))
                frameHandler.set_var(curr_inst.arg1, 'string', char)
            else:
                errorHandler.exit_with_error(53, 'CHYBA: Spatny typ operandu instrukce INT2CHAR (Typ: {})'.format(type))


    # print('ahoj')

if __name__ == '__main__':
    Main()

