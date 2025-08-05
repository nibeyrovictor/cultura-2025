import face_recognition
import os
import sys

def recognize_face(image_path):
    # El script busca las carpetas relative a su propia ubicación,
    # por lo que encontrará "known_faces" en el mismo directorio.
    known_faces_dir = "known_faces"
    known_face_encodings = []
    known_face_names = []

    for file_name in os.listdir(known_faces_dir):
        name = os.path.splitext(file_name)[0]
        image = face_recognition.load_image_file(os.path.join(known_faces_dir, file_name))
        
        # Comprobar si se encontraron caras en las imágenes conocidas
        encodings = face_recognition.face_encodings(image)
        if encodings:
            known_face_encodings.append(encodings[0])
            known_face_names.append(name)

    unknown_image = face_recognition.load_image_file(image_path)
    unknown_face_encodings = face_recognition.face_encodings(unknown_image)

    if not unknown_face_encodings:
        print("No se encontraron caras en la imagen.")
        return

    for unknown_face_encoding in unknown_face_encodings:
        matches = face_recognition.compare_faces(known_face_encodings, unknown_face_encoding)
        name = "Desconocido"

        if True in matches:
            first_match_index = matches.index(True)
            name = known_face_names[first_match_index]

        # Imprime solo el nombre para una salida limpia
        print(name)

if __name__ == "__main__":
    if len(sys.argv) > 1:
        image_to_check = sys.argv[1]
        recognize_face(image_to_check)