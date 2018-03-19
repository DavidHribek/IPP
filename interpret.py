from interpret_modules.errorHandler import ErrorHandler
from interpret_modules.xmlParser import XmlParser
from interpret_modules.argChecker import ArgChecker


def Main():
    errorHandler = ErrorHandler() # zajistuje chybove ukonceni programu
    argChecker = ArgChecker(errorHandler) # kontroluje argumenty programu
    argChecker.check()
    xmlParser = XmlParser(argChecker.get_file_path(), errorHandler) # kontroluje vstupni XML
    xmlParser.parse()

    print('konec')




if __name__ == '__main__':
    Main()