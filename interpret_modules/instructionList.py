# File:     instructionList.py
# Author:   David Hříbek (xhribe02)
# Date:     19.3.2018
# Desc:     Interpret jazyka ippcode18
#
class InstructionList(dict):
    def __init__(self):
        self.instructions = {}
        self.inst_order = 0 # citac instrukci

    def insertInst(self, instruction):
        self.inst_order += 1
        self.instructions[self.inst_order] = instruction

    def get_instruction_order(self):
        return self.inst_order

    def __iter__(self):
        for x in self.instructions.values():
            yield x
