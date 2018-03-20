import xml.etree.ElementTree as ET
import collections

class XmlParser():
    def __init__(self, source_file_path, errorHandler):
        self.source_file_path = source_file_path
        self.errorHandler = errorHandler

    def parse(self):
        self.checkXmlStructure()
        self.checkInstSyntax()

    def checkXmlStructure(self):
        try:
            tree = ET.parse(self.source_file_path)
            self.root = tree.getroot()
        except FileNotFoundError:
            # soubor neexistuje
            self.errorHandler.exit_with_error(11)
        except Exception:
            # spatna struktura XML (not well formated)
            self.errorHandler.exit_with_error(31)

        # Kontrola XML
        # ROOT ELEMENT: program
        if self.root.tag != 'program':
            self.errorHandler.exit_with_error(31)
        # ROOT ELEMENT: povolene atributy
        for atr in self.root.attrib:
            if atr not in ['language', 'name', 'description']:
                self.errorHandler.exit_with_error(31, 'CHYBA: Nepovolene atributy elementu program')
        # ROOT ELEMENT: atribut language
        if 'language' not in self.root.attrib:
            self.errorHandler.exit_with_error(31, 'CHYBA: Chybejici atribut language')
        # ROOT ELEMENT: atribut language obsahuje 'ippcode18'
        if str(self.root.attrib['language']).lower() != 'ippcode18':
            self.errorHandler.exit_with_error(31)
        # JEDNOTLIVE INSTRUKCE:
        instruction_order_numbers = []
        for instruction in self.root:
            # INSTRUKCE: nazev elementu
            if instruction.tag != 'instruction':
                self.errorHandler.exit_with_error(31, 'CHYBA: Spatny nazev elementu instrukce')
            # INSTRUKCE: atribut opcode
            if 'opcode' not in instruction.attrib:
                self.errorHandler.exit_with_error(31, 'CHYBA: Chybejici atribut opcode v elementu instrukce')
            # INSTRUKCE: atribut order
            if 'order' not in instruction.attrib:
                self.errorHandler.exit_with_error(31, 'CHYBA: Chybejici atribut order v elementu instrukce')
            else:
                instruction_order_numbers.append(instruction.attrib['order'])
            # ARGUMENTY INSTRUKCE
            arg_order = 0
            for argument in instruction:
                arg_order += 1
                # ARGUMENT: nazev elementu
                if argument.tag != 'arg'+str(arg_order):
                    self.errorHandler.exit_with_error(31, 'CHYBA: Spatny nazev elemntu parametru instrukce')
                # ARGUMENT: atribut type
                if 'type' not in argument.attrib:
                    self.errorHandler.exit_with_error(31, 'CHYBA: Chybejici atribut type v elementu parametru instrukce')
                # ARGUMENT: atribut type povolene hodnoty
                if argument.attrib['type'] not in ['int', 'bool', 'string', 'label', 'type', 'var']:
                    self.errorHandler.exit_with_error(32, 'CHYBA: Nepovolene hodnoty atributu type')
        # test instrukci s duplicitnim order number
        if len(instruction_order_numbers) != len(set(instruction_order_numbers)):
            self.errorHandler.exit_with_error(31, 'CHYBA: Instrukce s duplicitni hodnotou atributu order')

    def checkInstSyntax(self):
        """Lexikalni a synt. analyza jednotlivych instrukci"""

        def argCount(instruction):
            """Vrati pocet argumentu instrukce"""
            return len(list(instruction))

        for instruction in self.root:
            # prevod opcode na uppercase (neni case sensitive)
            instruction.attrib['opcode'] = str(instruction.attrib['opcode']).upper()
            print(instruction.attrib['opcode'])
            # kontrola jednotlivych instrukci
            if instruction.attrib['opcode'] in ['CREATEFRAME', 'PUSHFRAME', 'POPFRAME', 'BREAK', 'RETURN']: # none
                # pocet parametru
                if argCount(instruction) == 0:
                    # lex, syntax
                    pass
                else:
                    self.errorHandler.exit_with_error(32, 'CHYBA: Nespravny pocet parametru instrukce ({})'.format(instruction.attrib['opcode']))
            elif instruction.attrib['opcode'] in ['DEFVAR', 'POPS']: # <var>
                # pocet parametru
                if argCount(instruction) == 1:
                    # lex, syntax

                    pass
                else:
                    self.errorHandler.exit_with_error(32, 'CHYBA: Nespravny pocet parametru instrukce ({})'.format(instruction.attrib['opcode']))
            elif instruction.attrib['opcode'] in ['PUSHS', 'WRITE', 'DPRINT']: # <symb>
                # pocet parametru
                if argCount(instruction) == 1:
                    # lex, syntax
                    pass
                else:
                    self.errorHandler.exit_with_error(32, 'CHYBA: Nespravny pocet parametru instrukce ({})'.format(instruction.attrib['opcode']))
            elif instruction.attrib['opcode'] in ['CALL', 'JUMP', 'LABEL']: # <label>
                # pocet parametru
                if argCount(instruction) == 1:
                    # lex, syntax
                    pass
                else:
                    self.errorHandler.exit_with_error(32, 'CHYBA: Nespravny pocet parametru instrukce ({})'.format(instruction.attrib['opcode']))
            elif instruction.attrib['opcode'] in ['MOVE', 'NOT', 'INT2CHAR', 'TYPE', 'STRLEN']: # <var> <symb>
                # pocet parametru
                if argCount(instruction) == 2:
                    # lex, syntax
                    pass
                else:
                    self.errorHandler.exit_with_error(32, 'CHYBA: Nespravny pocet parametru instrukce ({})'.format(instruction.attrib['opcode']))
            elif instruction.attrib['opcode'] in ['ADD', 'SUB', 'MUL', 'IDIV', 'LT', 'GT', 'EQ', 'AND', 'OR', 'STRI2INT', 'CONCAT', 'GETCHAR', 'SETCHAR']: # <var> <symb1> <symb2>
                # pocet parametru
                if argCount(instruction) == 3:
                    # lex, syntax
                    pass
                else:
                    self.errorHandler.exit_with_error(32, 'CHYBA: Nespravny pocet parametru instrukce ({})'.format(instruction.attrib['opcode']))
            elif instruction.attrib['opcode'] == 'READ': # <var> <type>
                # pocet parametru
                if argCount(instruction) == 2:
                    # lex, syntax
                    pass
                else:
                    self.errorHandler.exit_with_error(32, 'CHYBA: Nespravny pocet parametru instrukce ({})'.format(instruction.attrib['opcode']))
            elif instruction.attrib['opcode'] in ['JUMPIFEQ', 'JUMPIFNEQ']: # <label> <symb1> <symb2>
                # pocet parametru
                if argCount(instruction) == 3:
                    # lex, syntax
                    pass
                else:
                    self.errorHandler.exit_with_error(32, 'CHYBA: Nespravny pocet parametru instrukce ({})'.format(instruction.attrib['opcode']))
            else:
                # nepovolena instrukce
                self.errorHandler.exit_with_error(32, 'CHYBA: Nepovoleny opcode instrukce ({})'.format(instruction.attrib['opcode']))

    def checkLabel(self):
        pass

    def checkVar(self):
        pass

    def checkSymb(self):
        pass

    def checkType(self):
        pass