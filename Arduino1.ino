#include <OneWire.h>
#include <DallasTemperature.h>
#include <DFRobot_PH.h>
#include <EEPROM.h>
#include <SPI.h>
#include <RF24.h>
// ------------- GENERAL --------------------
int sampleDelay = 100;
int sampleNum = 10;

// -------------- TEMP ---------------------- 
// Data wire of the temperatur sensor is plugged into the Arduino
#define ONE_WIRE_BUS 2
OneWire oneWire(ONE_WIRE_BUS);
DallasTemperature sensors(&oneWire);

// --------------- PH -----------------------
#define PH_PIN A1
//float voltage,phValue;
DFRobot_PH ph;

// -------------- ORP -----------------------
//#define ORP_VOLTAGE 5.00    //system voltage
//#define ORP_OFFSET 0        //zero drift voltage
#define ORP_PIN A2

// NRF24L01
RF24 radio(9, 10) ;  // ce, csn pins    


float randomFloat() {
  return random(-10000, 10000) / 100.0;
}

// ################################ TEMPERATURE FUNCTIONS ###########################

float getTemperature(int numSamples, int timeDelay) {
  float temps[numSamples];
  for (int i=0; i<numSamples ;i++) {
    temps[i] = measureTemperature();
    delay(timeDelay);
  }
  return normalizeMeasures(temps, numSamples);
}

float measureTemperature() {
    sensors.requestTemperatures();
    float r = sensors.getTempCByIndex(0);
    return r;
}

// ################################ PH FUNCTIONS ###########################

float getPh(float temp, int numSamples, int timeDelay) {
  float phs[numSamples];
  for (int i=0; i<numSamples ;i++) {
    phs[i] = measurePh(temp);
    delay(timeDelay);
  }
  return normalizeMeasures(phs, numSamples);
}

float measurePh(float temp) {
    float voltage = analogRead(PH_PIN)/1024.0*5000;  // read the voltage
    float phValue = ph.readPH(voltage,temp);  // convert voltage to pH with temperature compensation
    //ph.calibration(voltage,temp);    enable this to allow calibration through serial monitor
    ph.calibration(voltage,temp);
    return phValue;
}

// ################################ ORP FUNCTIONS ###########################

double getORP(int numSamples, int timeDelay) {
  double orp[numSamples];
  for (int i=0; i<numSamples ;i++) {
    orp[i] = measureORP();
    delay(timeDelay);
  }
  return normalizeDouble(orp, numSamples);
}

double measureORP() {
    double myValue = analogRead(ORP_PIN);
    double mV = ((30*(double)5000)-(75*myValue*5000/1024))/75;
//    Serial.println("VAL: " + String(myValue));
//    Serial.println("mV: " + String(mV));
    return mV;
}




// Ermittelt einen durchschnittlichen Messwert aus einem float array.
// Die Min- und Max werte aus dem Array werden verworfen, aus dem Rest wird der Durchschnitt berechnet.
// Der Array muss mindestens 3 Messwerte enthalten, anderenfalls gibt die Funktion "0" zurück.
// float data[]:  der datenarray
// byte datasize: die grösse des arrays (anzahl elemente)
float normalizeMeasures(float data[], byte dataSize) {
  if (dataSize > 2) {
    // Min und Max Werte ermitteln
    float dataMin = data[0];
    float dataMax = data[0];    
    float dataSum = 0;
    for (int i=0; i<dataSize; i++) {
      if (data[i] > dataMax) { dataMax = data[i]; }
      if (data[i] < dataMin) { dataMin = data[i]; }
      dataSum = dataSum + data[i];
    }
    dataSum = dataSum - dataMax - dataMin;
    float dataResult = dataSum / (dataSize-2);
    return dataResult;
  } else {
    return 0;
  }
}

float normalizeDouble(double data[], byte dataSize) {
  if (dataSize > 2) {
    // Min und Max Werte ermitteln
    double dataMin = data[0];
    double dataMax = data[0];    
    double dataSum = 0;
    for (int i=0; i<dataSize; i++) {
      if (data[i] > dataMax) { dataMax = data[i]; }
      if (data[i] < dataMin) { dataMin = data[i]; }
      dataSum = dataSum + data[i];
    }
    dataSum = dataSum - dataMax - dataMin;
    double dataResult = dataSum / (dataSize-2);
    return dataResult;
  } else {
    return 0;
  }
}

void sendData(String dataString) {
  int str_len = dataString.length() + 1; 
  char text[str_len];
  dataString.toCharArray(text, str_len);
  radio.write(&text, sizeof(text));  
}


void setup() {
  Serial.begin(9600);
  const uint64_t pipe = 0xE0E0F1F1E0LL ;    // pipe address same as sender i.e. raspberry pi
  radio.begin();        // start radio at ce csn pin 9 and 10
  radio.setPayloadSize(32) ;
  radio.setChannel(0x76) ;            // set chanel at 76
  radio.setDataRate(RF24_1MBPS) ;
  radio.setPALevel(RF24_PA_MAX) ;   // set power level
  radio.setAutoAck(true) ;
  radio.enableDynamicPayloads() ;
  radio.enableAckPayload() ;
  radio.disableCRC();
  radio.openWritingPipe(pipe) ;        // start reading pipe 
  radio.powerUp() ;      
  delay(2000);

  sensors.begin();
  ph.begin();
}

void loop() {
  float temp = getTemperature(sampleNum,sampleDelay);
  Serial.println("Temp: " + String(temp) + "°C");
  
  float pH = getPh(temp,sampleNum,sampleDelay);
  Serial.println("pH  : " + String(pH));
  
  double ORP = getORP(sampleNum,sampleDelay);
  Serial.println("ORP  : " + String(ORP) + "mV");

  String s = String(pH) + "," + String(ORP) + "," + String(temp);
  Serial.print("Sending data: " + s + " ...");
  sendData(s);
  Serial.println(" Data sent.");

//  delay(3000);
}
