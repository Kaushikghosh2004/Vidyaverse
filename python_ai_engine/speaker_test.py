import pyttsx3

print("--- TESTING VOICES ---")
engine = pyttsx3.init()
voices = engine.getProperty('voices')

if not voices:
    print("ERROR: No voices found on this Windows system!")
else:
    for index, voice in enumerate(voices):
        print(f"Voice ID {index}: {voice.name}")
        try:
            engine.setProperty('voice', voice.id)
            engine.say(f"Testing voice number {index}")
            engine.runAndWait()
        except:
            print(f"Voice {index} failed.")

print("\n----------------------")
print("Did you hear any of them? If yes, remember the ID number (0 or 1).")
input("Press Enter to close...")