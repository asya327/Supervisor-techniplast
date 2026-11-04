import paho.mqtt.client as mqtt
import time
import os
import sqlite3
import json
from random import randint, choice
from datetime import datetime
import smtplib
from email import EmailMessage # ou email.message

# ==========================================================
# 1. CONFIGURATION ET CHEMINS & GESTION DES SECRETS LOCAUX
# ==========================================================
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
os.chdir(BASE_DIR)

DB_PATH = os.path.join(BASE_DIR, "techniplast_data.db")
CONFIG_PATH = os.path.join(BASE_DIR, "config.json")

# Gestion de la configuration persistante sans fuite sur GitHub
if os.path.exists(CONFIG_PATH):
    with open(CONFIG_PATH, "r", encoding="utf-8") as f:
        config = json.load(f)
        EMAIL_SENDER = config.get("email_sender", "")
        EMAIL_PASSWORD = config.get("email_password", "")
        EMAIL_RECEIVER = config.get("email_receiver", "")
    print("--- [CONFIG] Identifiants chargés depuis le fichier local. ---")
else:
    print("=== PREMIER LANCEMENT : CONFIGURATION DES EMAILS ===")
    EMAIL_SENDER = input("Entrez votre email (expéditeur) : ").strip()
    EMAIL_PASSWORD = input("Entrez votre mot de passe d'application : ").strip()
    EMAIL_RECEIVER = input("Entrez l'email du destinataire (récepteur) : ").strip()
    
    # Sauvegarde automatique pour les prochains lancements
    config_data = {
        "email_sender": EMAIL_SENDER,
        "email_password": EMAIL_PASSWORD,
        "email_receiver": EMAIL_RECEIVER
    }
    with open(CONFIG_PATH, "w", encoding="utf-8") as f:
        json.dump(config_data, f, indent=4)
    print("--- [CONFIG] Identifiants sauvegardés localement dans config.json ---")

SEUIL_ALERTE = 180
HEURE_DEBUT_USINE = 8  
HEURE_FIN_USINE = 18


# ==========================================================
# 2. FONCTIONS DE GESTION
# ==========================================================

def est_dans_la_plage_horaire():
    """Vérifie si l'on est dans les heures de production."""
    heure_actuelle = datetime.now().hour
    return HEURE_DEBUT_USINE <= heure_actuelle < HEURE_FIN_USINE

def init_db():
    """Initialise la base de données SQLite."""
    conn = sqlite3.connect(DB_PATH, timeout=20)
    conn.execute("PRAGMA journal_mode=WAL;") 
    cursor = conn.cursor()
    cursor.execute("CREATE TABLE IF NOT EXISTS etat_presse (presse INTEGER PRIMARY KEY, etat TEXT, date_maj TEXT)")
    cursor.execute("CREATE TABLE IF NOT EXISTS instant_arrets (id INTEGER PRIMARY KEY AUTOINCREMENT, presse INTEGER, type_arret TEXT, date_debut TEXT)")
    cursor.execute("CREATE TABLE IF NOT EXISTS historique_arrets (id INTEGER PRIMARY KEY AUTOINCREMENT, presse INTEGER, type_arret TEXT, date_debut TEXT, date_fin TEXT, duree REAL)")
    conn.commit()
    conn.close()

def envoyer_email_alerte(presse, duree):
    """Envoie un mail seulement si on est dans la plage horaire."""
    if not est_dans_la_plage_horaire():
        print(f"--- [HORAIRE] Alerte bloquée : Presse {presse} en arrêt hors production. ---")
        return

    msg = EmailMessage()
    msg["From"] = EMAIL_SENDER
    msg["To"] = EMAIL_RECEIVER
    msg["Subject"] = f"ALERTE TECHNIPLAST : Arrêt Presse {presse}"
    
    corps = f"ALERTE : La Presse {presse} est à l'arrêt depuis {int(duree)} secondes."
    msg.set_content(corps)

    try:
        with smtplib.SMTP_SSL("smtp.gmail.com", 465) as server:
            server.login(EMAIL_SENDER, EMAIL_PASSWORD)
            server.send_message(msg)
        print(f"--- [EMAIL] Alerte envoyée pour Presse {presse} ---")
    except Exception as e:
        print(f"Erreur Email: {e}")

# ==========================================================
# 3. CONFIGURATION MQTT
# ==========================================================
client = mqtt.Client()
mqtt_connected = False
try:
    client.connect("127.0.0.1", 1883, 30)
    client.loop_start()
    mqtt_connected = True
except:
    print("MQTT: Broker non disponible (Mode local uniquement)")

# ==========================================================
# 4. BOUCLE DE SUPERVISION (Simulation intelligente)
# ==========================================================
init_db()
temps_arrets = [] 

print(f"Supervision Techniplast lancée (Horaires: {HEURE_DEBUT_USINE}h-{HEURE_FIN_USINE}h)...")

try:
    while True:
        # Simulation d'une presse aléatoire (1 à 5)
        p = randint(1, 5)
        etat = choice(["run"] * 5 + ["stop", "off", "maintenance"])
        horodate = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

        conn = sqlite3.connect(DB_PATH, timeout=10)
        cursor = conn.cursor()

        # A. Mise à jour de l'état actuel
        cursor.execute("REPLACE INTO etat_presse VALUES (?, ?, ?)", (p, etat, horodate))
        
        # B. Envoi MQTT
        if mqtt_connected:
            client.publish(f"techniplast/presse/{p}/status", etat, retain=True)

        # C. Analyse des changements d'état
        if etat == "run":
            # Si la machine redémarre, on calcule la durée de l'arrêt précédent
            for item in temps_arrets[:]:
                if item["presse"] == p:
                    duree_f = round(time.time() - item["temps"], 2)
                    cursor.execute("INSERT INTO historique_arrets (presse, type_arret, date_debut, date_fin, duree) VALUES (?, ?, ?, ?, ?)", 
                                   (p, item["type"], item["date"], horodate, duree_f))
                    temps_arrets.remove(item)
                    print(f"Presse {p} : REDÉMARRAGE (Arrêt total: {duree_f}s)")
        else:
            # Si un nouvel arrêt est détecté
            if not any(a["presse"] == p for a in temps_arrets):
                cursor.execute("INSERT INTO instant_arrets (presse, type_arret, date_debut) VALUES (?, ?, ?)", (p, etat, horodate))
                temps_arrets.append({"presse": p, "type": etat, "temps": time.time(), "date": horodate, "alerte_envoyee": False})
                print(f"Presse {p} : ARRÊT DÉTECTÉ ({etat.upper()})")

        # D. Surveillance des seuils d'alertes
        for item in temps_arrets:
            duree_actuelle = time.time() - item["temps"]
            if duree_actuelle > SEUIL_ALERTE and not item["alerte_envoyee"]:
                envoyer_email_alerte(item["presse"], duree_actuelle)
                item["alerte_envoyee"] = True

        conn.commit()
        conn.close()
        time.sleep(3) # Fréquence de lecture

except Exception as e:
    print(f"Erreur fatale : {e}")