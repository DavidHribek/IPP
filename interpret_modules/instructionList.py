# File:     instructionList.py
# Author:   David Hříbek (xhribe02)
# Date:     19.3.2018
# Desc:     Interpret jazyka ippcode18
#
class InstructionList(dict):
    def __init__(self):
        self.instructions = {}
        self.inst_number = 0 # interni citac instrukci
        self.instraction_counter = 1; # pozice aktualni instrukce

    def insertInst(self, instruction):
        """Vlozi instrukci na instrukcni pasku"""
        self.inst_number += 1
        self.instructions[self.inst_number] = instruction

    def get_instruction_number(self):
        """Vrati pocet instrukci na instrukcni pasce"""
        return self.inst_number

    def get_instruction_counter(self):
        """Vrati hodnotu instrukcniho citace (pozici v kodu)"""
        return self.instraction_counter

    def get_next_instruction(self):
        """Vrati dalsi instrukci v poradi"""
        if self.instraction_counter <= self.inst_number:
            # vrati dalsi instrukce
            self.instraction_counter += 1  # zvyseni pozice aktualni pozice o 1
            return self.instructions[self.instraction_counter-1]
        else:
            return None


    def __iter__(self):
        for x in self.instructions.values():
            yield x
