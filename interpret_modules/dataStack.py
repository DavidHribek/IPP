# File:     dataStack.py
# Author:   David Hříbek (xhribe02)
# Date:     19.3.2018
# Desc:     Interpret jazyka ippcode18
#
from interpret_modules.errorHandler import ErrorHandler

class DataStack(ErrorHandler):
    def __init__(self):
        self.stack = []

    def pushValue(self, value):
        """Vlozi hodnotu na vrchol datoveho zasobniku"""
        self.stack.append(value)

    def popValue(self):
        if len(self.stack) > 0:
            # TODO
            self.stack.pop()
            pass
        else:
            self.exit_with_error(56, 'CHYBA: Chybejici hodnota na datovem zasobniku')

