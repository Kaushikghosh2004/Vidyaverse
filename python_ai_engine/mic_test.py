import speech_recognition as sr

print("\n--- SCANNING AUDIO DEVICES ---")
mics = sr.Microphone.list_microphone_names()

if not mics:
    print("ERROR: No microphones found on this computer!")
else:
    for index, name in enumerate(mics):
        print(f"ID: {index} | Device: {name}")

print("\n------------------------------")
print("Please reply to Gemini with the LIST above.")
input("Press Enter to close...")