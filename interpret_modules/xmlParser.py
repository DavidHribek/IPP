import xml.etree.ElementTree as ET

class XmlParser():
    def __init__(self, source_file_path, errorHandler):
        self.source_file_path = source_file_path
        self.errorHandler = errorHandler

    def parse(self):
        try:
            tree = ET.parse(self.source_file_path)
            root = tree.getroot()
        except FileNotFoundError:
            # soubor neexistuje
            self.errorHandler.exit_with_error(11)
        except Exception:
            # spatna struktura XML (not well formated)
            self.errorHandler.exit_with_error(31)

        # Kontrola XML
        # ROOT ELEMENT: program
        if root.tag != 'program':
            self.errorHandler.exit_with_error(31)
        # ROOT ELEMENT: povolene atributy
        for atr in root.attrib:
            if atr not in ['language', 'name', 'description']:
                self.errorHandler.exit_with_error(31)
        # ROOT ELEMENT: atribut language
        if 'language' not in root.attrib:
            self.errorHandler.exit_with_error(31)
        # ROOT ELEMENT: atribut language obsahuje 'ippcode18'
        if str(root.attrib['language']).lower() != 'ippcode18':
            self.errorHandler.exit_with_error(31)
        # JEDNOTLIVE INSTRUKCE:
        instruction_order_numbers = []
        for instruction in root:
            # INSTRUKCE: nazev elementu
            if instruction.tag != 'instruction':
                self.errorHandler.exit_with_error(31)
            # INSTRUKCE: atribut opcode
            if 'opcode' not in instruction.attrib:
                self.errorHandler.exit_with_error(31)
            # INSTRUKCE: atribut order
            if 'order' not in instruction.attrib:
                self.errorHandler.exit_with_error(31)
            else:
                instruction_order_numbers.append(instruction.attrib['order'])
            # ARGUMENTY INSTRUKCE
            arg_order = 0
            for argument in instruction:
                arg_order += 1
                # ARGUMENT: nazev elementu
                if argument.tag != 'arg'+str(arg_order):
                    self.errorHandler.exit_with_error(31)
                # ARGUMENT: atribut type
                if 'type' not in argument.attrib:
                    self.errorHandler.exit_with_error(31)
                # ARGUMENT: atribut type povolene hodnoty
                if argument.attrib['type'] not in ['int', 'bool', 'string', 'label', 'type', 'var']:
                    self.errorHandler.exit_with_error(32)
        # test instrukci s duplicitnim order number
        if len(instruction_order_numbers) != len(set(instruction_order_numbers)):
            self.errorHandler.exit_with_error(31)

        return root