from interpret_modules.errorHandler import ErrorHandler

class FrameStack(ErrorHandler):
    def __init__(self):
        self.stack = []
        self.temporaryFrame = None

    def set_temporary_frame(self, temporary_frame):
        """Ulozi si referenci na temporary frame pro pozdejsi vyuziti"""
        self.temporaryFrame = temporary_frame

    def push_temporary_frame(self, temporary_frame):
        """Vlozi TF do zasobniku ramcu"""
        self.stack.append(temporary_frame)

    def pop_frame_to_temporary_frame(self):
        """Presune ramec z vrcholu zasobniku ramcu do TF"""
        if len(self.stack) > 0:
            # zasobnik ramcu obsahuje ramce, muzeme provest presun
            self.temporaryFrame.set_new_frame(self.stack.pop())
        else:
            self.exit_with_error(55, 'CHYBA: Pokus o presun ramce z prazdneho zasobniku ramcu')

    def get_frame_stack(self):
        """Vrati cely zasobnik ramcu"""
        return self.stack