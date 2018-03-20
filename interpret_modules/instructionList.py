# File:     instructionList.py
# Author:   David Hříbek (xhribe02)
# Date:     19.3.2018
# Desc:     Interpret jazyka ippcode18
#
class InstructionList(dict):
    def __init__(self):
        self.instructions = {}
        self.inst_in_file = 0                # pocet instrukci v souboru
        self.instruction_counter = 1         # interni citac instrukci
        self.instruction_done_number = 0     # pocet vykonanych instrukci

    def insertInst(self, instruction):
        """Vlozi instrukci na instrukcni pasku"""
        self.inst_in_file += 1
        self.instructions[self.inst_in_file] = instruction

    def get_next_instruction(self):
        """Vrati dalsi instrukci v poradi (tim ji povazuje za provedenou)"""
        if self.instruction_counter <= self.inst_in_file:
            # zaznamenani vykonane instrukce
            self.instruction_done_number += 1
            # vrati dalsi instrukce
            self.instruction_counter += 1  # zvyseni pozice aktualni pozice o 1
            return self.instructions[self.instruction_counter-1]
        else:
            return None

    # def get_instruction_number(self):
    #     """Vrati pocet instrukci na instrukcni pasce"""
    #     return self.inst_in_file

    def get_instruction_counter(self):
        """Vrati hodnotu instrukcniho citace (pozici v kodu)"""
        return self.instruction_counter -1

    def get_instruction_done_number(self):
        """Vrati pocet vykonanych instrukci"""
        return self.instruction_done_number - 1

    def __iter__(self):
        for x in self.instructions.values():
            yield x
