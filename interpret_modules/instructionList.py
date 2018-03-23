# File:     instructionList.py
# Author:   David Hříbek (xhribe02)
# Date:     19.3.2018
# Desc:     Interpret jazyka ippcode18
#
from interpret_modules.errorHandler import ErrorHandler
class InstructionList(ErrorHandler):
    def __init__(self):
        self.instructions = {}               # slovnik instrukci (klicem instrukce je pozice v kodu)
        self.instructions_in_file = 0        # pocet instrukci v souboru
        self.instruction_counter = 1         # interni citac instrukci
        self.instruction_done_number = 0     # pocet vykonanych instrukci
        self.labels = {}                     # slovnik navesti programu (klicem je nazev, hodnotou je pozice v kodu)

    def insert_instruction(self, instruction):
        """Vlozi instrukci na instrukcni pasku"""
        self.instructions_in_file += 1
        self.instructions[self.instructions_in_file] = instruction
        # sprava navesti
        if instruction.opcode == 'LABEL':
            # vlozeni instrukce navesti navic do slovniku navesti
            label_name = instruction.arg1['text']
            if label_name in self.labels:
                # navesti s timto nazvem existuje
                self.exit_with_error(52, 'CHYBA: Duplicitni nazev navesti ({})'.format(label_name))
            else:
                # navesti jeste neexistuje, pridat
                self.labels[label_name] = self.instructions_in_file # zaznamenani pozice navesti v kodu pro skoky

    def get_next_instruction(self):
        """Vrati dalsi instrukci v poradi (tim ji povazuje za provedenou)"""
        if self.instruction_counter <= self.instructions_in_file:
            # pokud je na instrukcni pasce dalsi instrukce
            self.instruction_done_number += 1 # zaznamenani dalsi vykonane instrukce
            self.instruction_counter += 1  # zvyseni instrukcniho citace o 1
            return self.instructions[self.instruction_counter-1] # vraceni aktualni instrukce
        else:
            return None

    def jump_to_label(self, i_arg_label):
        """Nastavi interni citac instrukci na hodnotu daneho navesti"""
        label = i_arg_label['text']
        if label in self.labels:
            # navesti je ve slovniku navesti
            self.instruction_counter = self.labels[label] # instrukcni citac je prepsan pozici navesti
        else:
            # navesti neexistuje
            self.exit_with_error(52, 'CHYBA: Skok na neexistujici navesti')

    # def get_instruction_number(self):
    #     """Vrati pocet instrukci na instrukcni pasce"""
    #     return self.inst_in_file

    def get_instruction_counter(self):
        """Vrati hodnotu instrukcniho citace (pozici v kodu)"""
        return self.instruction_counter -1

    def get_instruction_done_number(self):
        """Vrati pocet vykonanych instrukci"""
        return self.instruction_done_number - 1