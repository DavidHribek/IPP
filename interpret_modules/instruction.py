# File:     instruction.py
# Author:   David Hříbek (xhribe02)
# Date:     19.3.2018
# Desc:     Interpret jazyka ippcode18
#
class Instruction():
    def __init__(self, opcode, arg1=None, arg2=None, arg3=None):
        self.opcode = opcode
        self.arg_count = 0
        if arg1 is not None:
            self.arg1 = {'type': arg1.attrib['type'], 'name': arg1.text}
            self.arg_count += 1
        if arg2 is not None:
            self.arg2 = {'type': arg2.attrib['type'], 'name': arg2.text}
            self.arg_count += 1
        if arg3 is not None:
            self.arg3 = {'type': arg3.attrib['type'], 'name': arg3.text}
            self.arg_count += 1
