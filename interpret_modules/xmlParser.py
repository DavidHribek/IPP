# File:     xmlParser.py
# Author:   David Hříbek (xhribe02)
# Date:     19.3.2018
# Desc:     Interpret jazyka ippcode18
#
import xml.etree.ElementTree as ET
import re
from interpret_modules.instruction import Instruction
from interpret_modules.errorHandler import ErrorHandler

class XmlParser(ErrorHandler):
    def __init__(self, source_file_path, instList):
        self.source_file_path = source_file_path
        self.instList = instList

    def parse(self):
        self.checkXmlStructure()
        self.checkInstSyntax()

    def checkXmlStructure(self):
        try:
            tree = ET.parse(self.source_file_path)
            self.root = tree.getroot()
        except FileNotFoundError:
            # soubor neexistuje
            self.exit_with_error(11, 'CHYBA: Soubor se nepodarilo otevrit ({})'.format(self.source_file_path))
        except Exception as e:
            # spatna struktura XML (not well formated)
            self.exit_with_error(31, 'CHYBA: XML neni dobre formatovany nebo nema ocekavanou strukturu (radek {}, sloupec {})'.format(e.position[0], e.position[1]))

        # Kontrola XML
        # ROOT ELEMENT: program
        if self.root.tag != 'program':
            self.exit_with_error(31, 'CHYBA: Root element musi byt <program>')
        # ROOT ELEMENT: povolene atributy
        for atr in self.root.attrib:
            if atr not in ['language', 'name', 'description']:
                self.exit_with_error(31, 'CHYBA: Nepovolene atributy elementu <program>')
        # ROOT ELEMENT: atribut language
        if 'language' not in self.root.attrib:
            self.exit_with_error(31, 'CHYBA: Chybejici atribut language')
        # ROOT ELEMENT: atribut language obsahuje 'ippcode18'
        if str(self.root.attrib['language']).lower() != 'ippcode18':
            self.exit_with_error(31)
        # JEDNOTLIVE INSTRUKCE:
        instruction_order_numbers = []
        for instruction in self.root:
            # INSTRUKCE: nazev elementu
            if instruction.tag != 'instruction':
                self.exit_with_error(31, 'CHYBA: Spatny nazev elementu instrukce ({}) ({}. instrukce XML souboru)'.format(instruction.tag, len(instruction_order_numbers)+1))
            # INSTRUKCE: atribut opcode
            if 'opcode' not in instruction.attrib:
                self.exit_with_error(31, 'CHYBA: Chybejici atribut opcode v elementu instrukce')
            # INSTRUKCE: atribut order
            if 'order' not in instruction.attrib:
                self.exit_with_error(31, 'CHYBA: Chybejici atribut order v elementu instrukce')
            else:
                instruction_order_numbers.append(instruction.attrib['order'])
            # ARGUMENTY INSTRUKCE
            arg_order = 0
            for argument in instruction:
                arg_order += 1
                # ARGUMENT: nazev elementu
                if argument.tag != 'arg'+str(arg_order):
                    self.exit_with_error(31, 'CHYBA: Spatny nazev elementu parametru instrukce')
                # ARGUMENT: atribut type
                if 'type' not in argument.attrib:
                    self.exit_with_error(31, 'CHYBA: Chybejici atribut type v elementu parametru instrukce')
                # ARGUMENT: atribut type povolene hodnoty
                if argument.attrib['type'] not in ['int', 'bool', 'string', 'label', 'type', 'var']:
                    self.exit_with_error(32, 'CHYBA: Nepovolene hodnoty atributu type')
        # test instrukci s duplicitnim order number
        if len(instruction_order_numbers) != len(set(instruction_order_numbers)):
            self.exit_with_error(31, 'CHYBA: Instrukce s duplicitni hodnotou atributu order')

    def checkInstSyntax(self):
        """Lexikalni a synt. analyza jednotlivych instrukci"""

        def argCount(instruction):
            """Vrati pocet argumentu instrukce"""
            return len(list(instruction))

        for instruction in self.root:
            # prevod opcode na uppercase (neni case sensitive)
            # instruction.attrib['opcode'] = str(instruction.attrib['opcode']).upper()
            # print(instruction.attrib['opcode']) # DEBUG
            # kontrola jednotlivych instrukci
            if instruction.attrib['opcode'] in ['CREATEFRAME', 'PUSHFRAME', 'POPFRAME', 'BREAK', 'RETURN']: # none
                # pocet parametru
                if argCount(instruction) == 0:
                    # vlozeni instrukce do instrukcni pasky
                    i = Instruction(instruction.attrib['opcode'])
                    self.instList.insertInst(i)
                else:
                    self.exit_with_error(31, 'CHYBA: Nespravny pocet parametru instrukce ({})'.format(instruction.attrib['opcode']))
            elif instruction.attrib['opcode'] in ['DEFVAR', 'POPS']: # <var>
                # pocet parametru
                if argCount(instruction) == 1:
                    # lex, syntax
                    self.checkVar(instruction[0])
                    # vlozeni instrukce do instrukcni pasky
                    i = Instruction(instruction.attrib['opcode'], arg1=instruction[0])
                    self.instList.insertInst(i)
                else:
                    self.exit_with_error(31, 'CHYBA: Nespravny pocet parametru instrukce ({})'.format(instruction.attrib['opcode']))
            elif instruction.attrib['opcode'] in ['PUSHS', 'WRITE', 'DPRINT']: # <symb>
                # pocet parametru
                if argCount(instruction) == 1:
                    # lex, syntax
                    self.checkSymb(instruction[0])
                    # vlozeni instrukce do instrukcni pasky
                    i = Instruction(instruction.attrib['opcode'], arg1=instruction[0])
                    self.instList.insertInst(i)
                else:
                    self.exit_with_error(31, 'CHYBA: Nespravny pocet parametru instrukce ({})'.format(instruction.attrib['opcode']))
            elif instruction.attrib['opcode'] in ['CALL', 'JUMP', 'LABEL']: # <label>
                # pocet parametru
                if argCount(instruction) == 1:
                    # lex, syntax
                    self.checkLabel(instruction[0])
                    # vlozeni instrukce do instrukcni pasky
                    i = Instruction(instruction.attrib['opcode'], arg1=instruction[0])
                    self.instList.insertInst(i)
                else:
                    self.exit_with_error(31, 'CHYBA: Nespravny pocet parametru instrukce ({})'.format(instruction.attrib['opcode']))
            elif instruction.attrib['opcode'] in ['MOVE', 'NOT', 'INT2CHAR', 'TYPE', 'STRLEN']: # <var> <symb>
                # pocet parametru
                if argCount(instruction) == 2:
                    # lex, syntax
                    self.checkVar(instruction[0])
                    self.checkSymb(instruction[1])
                    # vlozeni instrukce do instrukcni pasky
                    i = Instruction(instruction.attrib['opcode'], arg1=instruction[0], arg2=instruction[1])
                    self.instList.insertInst(i)
                else:
                    self.exit_with_error(31, 'CHYBA: Nespravny pocet parametru instrukce ({})'.format(instruction.attrib['opcode']))
            elif instruction.attrib['opcode'] in ['ADD', 'SUB', 'MUL', 'IDIV', 'LT', 'GT', 'EQ', 'AND', 'OR', 'STRI2INT', 'CONCAT', 'GETCHAR', 'SETCHAR']: # <var> <symb1> <symb2>
                # pocet parametru
                if argCount(instruction) == 3:
                    # lex, syntax
                    self.checkVar(instruction[0])
                    self.checkSymb(instruction[1])
                    self.checkSymb(instruction[2])
                    # vlozeni instrukce do instrukcni pasky
                    i = Instruction(instruction.attrib['opcode'], arg1=instruction[0], arg2=instruction[1], arg3=instruction[2])
                    self.instList.insertInst(i)
                else:
                    self.exit_with_error(31, 'CHYBA: Nespravny pocet parametru instrukce ({})'.format(instruction.attrib['opcode']))
            elif instruction.attrib['opcode'] == 'READ': # <var> <type>
                # pocet parametru
                if argCount(instruction) == 2:
                    # lex, syntax
                    self.checkVar(instruction[0])
                    self.checkType(instruction[1])
                    # vlozeni instrukce do instrukcni pasky
                    i = Instruction(instruction.attrib['opcode'], arg1=instruction[0], arg2=instruction[1])
                    self.instList.insertInst(i)
                else:
                    self.exit_with_error(31, 'CHYBA: Nespravny pocet parametru instrukce ({})'.format(instruction.attrib['opcode']))
            elif instruction.attrib['opcode'] in ['JUMPIFEQ', 'JUMPIFNEQ']: # <label> <symb1> <symb2>
                # pocet parametru
                if argCount(instruction) == 3:
                    # lex, syntax
                    self.checkLabel(instruction[0])
                    self.checkSymb(instruction[1])
                    self.checkSymb(instruction[2])
                    # vlozeni instrukce do instrukcni pasky
                    i = Instruction(instruction.attrib['opcode'], arg1=instruction[0], arg2=instruction[1], arg3=instruction[2])
                    self.instList.insertInst(i)
                else:
                    self.exit_with_error(31, 'CHYBA: Nespravny pocet parametru instrukce ({})'.format(instruction.attrib['opcode']))
            else:
                # nepovolena instrukce
                self.exit_with_error(32, 'CHYBA: Nepovoleny opcode instrukce ({})'.format(instruction.attrib['opcode']))

    def checkLabel(self, arg):
        """Kontrola validity navesti"""
        if arg.attrib['type'] != 'label':
            self.exit_with_error(32, 'CHYBA: Chybny atribut type u navesti ({})'.format(arg.attrib['type']))
        if not re.match('^(_|-|\$|&|%|\*|[a-zA-Z])(_|-|\$|&|%|\*|[a-zA-Z0-9])*$', arg.text):
            self.exit_with_error(32, 'CHYBA: Chybne navesti ({})'.format(arg.text))

    def checkVar(self, arg):
        """Kontrola validity promenne"""
        if arg.attrib['type'] != 'var':
            self.exit_with_error(32, 'CHYBA: Chybny atribut type u promenne ({})'.format(arg.attrib['type']))
        if not re.match('^(GF|LF|TF)@(_|-|\$|&|%|\*|[a-zA-Z])(_|-|\$|&|%|\*|[a-zA-Z0-9])*$', arg.text):
            self.exit_with_error(32, 'CHYBA: Chybny nazev promenne ({})'.format(arg.text))

    def checkSymb(self, arg):
        """Kontrola validity symbolu (konstanta/promenna)"""
        if arg.attrib['type'] in ['int', 'bool', 'string']:
            if arg.attrib['type'] == 'int':
                # int
                if not re.match('^([+-]?[1-9][0-9]*|[+-][0-9])$', arg.text):
                    self.exit_with_error(32, 'CHYBA: Chybna hodnota int ({})'.format(arg.text))
            elif arg.attrib['type'] == 'bool':
                # bool
                if not arg.text in ['true', 'false']:
                    self.exit_with_error(32, 'CHYBA: Chybna hodnota bool ({})'.format(arg.text))
            else:
                # string
                if not re.search('^(\\\\[0-9]{3}|[^\s\\\\#])*$', arg.text):
                    self.exit_with_error(32, 'CHYBA: Chybna hodnota string ({})'.format(arg.text))
        elif arg.attrib['type'] == 'var':
            # var
            self.checkVar(arg)
        else:
            # <symb> musi byt int/bool/string/var
            self.exit_with_error(32, 'CHYBA: Chybny atribut type u symbolu ({})'.format(arg.attrib['type']))

    def checkType(self, arg):
        """Kontrola validity type"""
        if arg.attrib['type'] != 'type':
            self.exit_with_error(32, 'CHYBA: Chybny atribut type u typu ({})'.format(arg.attrib['type']))
        if not re.match('^(int|bool|string)$', arg.text):
            self.exit_with_error(32, 'CHYBA: Chybny typ ({})'.format(arg.text))