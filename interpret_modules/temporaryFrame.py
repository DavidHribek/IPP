from interpret_modules.errorHandler import ErrorHandler

class TemporaryFrame(ErrorHandler):
    def __init__(self, frameStack):
        self.undefined = True       # urcuje, jeslit je zasobnik definovan (lze s nim pracovat) nebo ne
        self.frame = {}
        self.frameStack = frameStack

    def create_new_frame(self):
        """Inicializuje novy ramec"""
        self.undefined = False
        self.frame = {}

    def set_new_frame(self, frame_from_stack_frame):
        """Prepise se ramecem obdrzenym ze zasobniku ramcu"""
        self.frame = frame_from_stack_frame
        self.undefined = False

    def push_frame_to_frame_stack(self):
        """Vlozi TF na zasobnik ramcu, po provedeni bude TF nedefinovan"""
        if self.undefined == False:
            # ramec je definovan, muze byt presunut do zasobniku ramcu
            self.frameStack.push_temporary_frame(self.frame)
        else:
            self.exit_with_error(55, 'CHYBA: Pokus o presun nedefinovaneho TF na zasobnik ramcu')
        self.undefined = True

    def get_frame(self):
        """Vrati obsah TF"""
        if self.undefined == True:
            # TF je nedefinovan
            return 'NEDEFINOVAN'
        else:
            return self.frame