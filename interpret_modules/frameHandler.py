# File:     frameHandler.py
# Author:   David Hříbek (xhribe02)
# Date:     19.3.2018
# Desc:     Interpret jazyka ippcode18
#
from interpret_modules.errorHandler import ErrorHandler

class FrameHandler(ErrorHandler):
    def __init__(self):
        # ZASOBNIK RAMCU
        self.frame_stack = []
        # DOCASNY RAMEC (TF)
        self.undefined = True
        self.tmp_frame = {}
        # GLOBALNI RAMEC
        self.global_frame = {}

    # PRACE S RAMCI

    def create_tmp_frame(self):
        """Inicializuje novy docasny ramec"""
        self.undefined = False
        self.tmp_frame = {}

    def push_tmp_frame_to_frame_stack(self, ):
        """Vlozi TF na zasobnik ramcu, po provedeni bude TF nedefinovan"""
        if self.undefined == False:
            # docasny ramec je definovan, muze byt presunut do zasobniku ramcu
            self.frame_stack.append(self.tmp_frame)
        else:
            self.exit_with_error(55, 'CHYBA: Pokus o presun nedefinovaneho TF na zasobnik ramcu')
        self.undefined = True

    def pop_frame_stack_to_temporary_frame(self):
        """Presune ramec z vrcholu zasobniku ramcu do TF"""
        if len(self.frame_stack) > 0:
            # zasobnik ramcu obsahuje ramce, muzeme provest presun
            self.tmp_frame = self.frame_stack.pop()
            self.undefined = False
        else:
            self.exit_with_error(55, 'CHYBA: Pokus o presun ramce z prazdneho zasobniku ramcu')

    def get_frame_stack(self):
        """Vrati cely zasobnik ramcu"""
        return self.frame_stack

    def get_frame(self, frame):
        """Vrati pozadovany ramec nebo NEDEFINOVAN"""
        if frame == 'GF':
            return self.global_frame
        elif frame == 'LF':
            if len(self.frame_stack) > 0:
                # v zasobniku se nachazi ramce, muzeme ziskat obsah lokalniho ramce
                return self.frame_stack[len(self.frame_stack) - 1]
            else:
                return 'NEDEFINOVAN'
        elif frame == 'TF':
            if self.undefined == True:
                # TF je nedefinovan
                return 'NEDEFINOVAN'
            else:
                return self.tmp_frame



    def parse_arg_variable_frame_and_name(self, i_arg_variable):
        """Vrati ramec a nazev promenne zvlast"""
        return i_arg_variable['text'].split('@', 1)

    def get_arg_type_and_value(self, i_arg):
        if i_arg['type'] in ['int', 'bool', 'string', 'type', 'label']:
            # vrati typ literalu + hodnotu
            return (i_arg['type'], i_arg['text'])
        else:
            # symb je promenna
            frame, name = self.parse_arg_variable_frame_and_name(i_arg)
            frame_to_search = self.get_frame(frame)
            if frame_to_search == 'NEDEFINOVAN':
                self.exit_with_error(55, 'CHYBA: Pokus o cteni promenne z nedefinovaneho ramce (Ramec: {}, Promenna: {})'.format(frame, name))
            elif name not in frame_to_search:
                self.exit_with_error(54, 'CHYBA: Pokus o cteni neexistujici promenne (Ramec: {}, Promenna: {})'.format(frame, name))
            else:
                type = frame_to_search[name]['type']
                value = frame_to_search[name]['value']
                return (type, value)

    def set_var(self, i_arg_variable, type, value):
        """Vlozi novou hodnotu do promenne i_arg_variable"""
        frame, name = self.parse_arg_variable_frame_and_name(i_arg_variable)
        frame_to_search = self.get_frame(frame)
        if frame_to_search == 'NEDEFINOVAN':
            self.exit_with_error(55, 'CHYBA: Pokus o zapis do promenne na nedefinovanem ramci (Ramec {}, Nazev: {})'.format(frame, name))
        elif name not in frame_to_search:
            self.exit_with_error(54, 'CHYBA: Pokus o zapis do neexistujici promenne (Ramec: {}, Nazev: {})'.format(frame, name))
        else:
            # promenna existuje, muzeme do ni zapsat hodnotu
            frame_to_search[name]['type'] = type
            frame_to_search[name]['value'] = value



    # INSTRUKCE

    # DEFVAR
    def defvar(self, i_arg):
        frame, name = self.parse_arg_variable_frame_and_name(i_arg)
        frame_to_insert = self.get_frame(frame)
        if frame_to_insert == 'NEDEFINOVAN':
            # nedefinovany ramec
            self.exit_with_error(55, 'CHYBA: Pokus o vytvoreni promenne na nedefinovanem ramci (Ramec: {}, Nazev: {})'.format(frame, name))
        else:
            # ramec existuje
            if name in frame_to_insert:
                # promenna jiz existuje
                self.exit_with_error(58, 'CHYBA: Pokus o deklaraci existujici promenne (Ramec: {}, Nazev: {})'.format(frame, name))
            else:
                # promenna neexistuje, muzeme ji vlozit
                frame_to_insert[name] = {'value': None, 'type': None}

