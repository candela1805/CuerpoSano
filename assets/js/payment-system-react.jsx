import React, { useState, useEffect } from 'react';
import { CreditCard, DollarSign, QrCode, Building, Wallet, CheckCircle, XCircle, Clock, User, Calendar, Tag } from 'lucide-react';

const PaymentSystem = () => {
  const [step, setStep] = useState(1);
  const [members, setMembers] = useState([]);
  const [membershipTypes, setMembershipTypes] = useState([]);
  const [discounts, setDiscounts] = useState([]);
  const [selectedMember, setSelectedMember] = useState(null);
  const [selectedMembership, setSelectedMembership] = useState(null);
  const [selectedDiscount, setSelectedDiscount] = useState(null);
  const [paymentMethod, setPaymentMethod] = useState('');
  const [paymentData, setPaymentData] = useState({
    cardNumber: '',
    cardName: '',
    expiryDate: '',
    cvv: '',
    reference: '',
    amount: 0
  });
  const [qrCode, setQrCode] = useState('');
  const [processing, setProcessing] = useState(false);
  const [paymentResult, setPaymentResult] = useState(null);

  // Simular carga de datos
  useEffect(() => {
    // Simular miembros
    setMembers([
      { idMiembro: 1, dni: '99887766', nombre: 'Ana Martínez', email: 'ana.martinez@email.com', estado: 'activo' },
      { idMiembro: 2, dni: '55443322', nombre: 'Pedro Rodríguez', email: 'pedro.rodriguez@email.com', estado: 'activo' }
    ]);

    // Simular tipos de membresía
    setMembershipTypes([
      { idTipo: 1, nombre: 'Básica', descripcion: 'Acceso al gimnasio general', precioBase: 50.00, duracionDias: 30 },
      { idTipo: 2, nombre: 'Premium', descripcion: 'Acceso con clases grupales', precioBase: 80.00, duracionDias: 30 },
      { idTipo: 3, nombre: 'VIP', descripcion: 'Acceso con entrenador personal', precioBase: 150.00, duracionDias: 30 },
      { idTipo: 4, nombre: 'Anual', descripcion: 'Membresía anual con descuento', precioBase: 500.00, duracionDias: 365 }
    ]);

    // Simular descuentos
    setDiscounts([
      { idDescuento: 1, descripcion: 'Descuento estudiante', porcentaje: 15.00, tipo: 'Estudiante' },
      { idDescuento: 2, descripcion: 'Descuento tercera edad', porcentaje: 20.00, tipo: 'Adulto Mayor' },
      { idDescuento: 3, descripcion: 'Promoción verano', porcentaje: 25.00, tipo: 'Temporal' }
    ]);
  }, []);

  // Calcular monto total
  const calculateTotal = () => {
    if (!selectedMembership) return 0;
    let total = selectedMembership.precioBase;
    if (selectedDiscount) {
      const discount = (total * selectedDiscount.porcentaje) / 100;
      total -= discount;
    }
    return total.toFixed(2);
  };

  // Generar QR Code
  const generateQR = () => {
    const amount = calculateTotal();
    const qrData = {
      tipo: 'pago_membresia',
      miembro: selectedMember?.nombre,
      monto: amount,
      membresia: selectedMembership?.nombre,
      referencia: `QR-${Date.now()}`,
      fecha: new Date().toISOString()
    };
    
    // Simulación de QR (en producción usar una librería real)
    const qrString = btoa(JSON.stringify(qrData));
    setQrCode(qrString);
    setPaymentData({ ...paymentData, reference: qrData.referencia, amount });
  };

  // Procesar pago
  const processPayment = async () => {
    setProcessing(true);
    
    // Simular llamada a API
    await new Promise(resolve => setTimeout(resolve, 2000));

    const paymentInfo = {
      idMiembro: selectedMember.idMiembro,
      monto: calculateTotal(),
      fechaPago: new Date().toISOString().split('T')[0],
      metodoPago: paymentMethod,
      referencia: paymentData.reference || `REF-${Date.now()}`,
      estado: 'completado'
    };

    setPaymentResult({
      success: true,
      message: 'Pago procesado exitosamente',
      data: paymentInfo
    });
    
    setProcessing(false);
    setStep(4);
  };

  const resetPayment = () => {
    setStep(1);
    setSelectedMember(null);
    setSelectedMembership(null);
    setSelectedDiscount(null);
    setPaymentMethod('');
    setPaymentData({
      cardNumber: '',
      cardName: '',
      expiryDate: '',
      cvv: '',
      reference: '',
      amount: 0
    });
    setQrCode('');
    setPaymentResult(null);
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 p-4">
      <div className="max-w-4xl mx-auto">
        {/* Header */}
        <div className="bg-white rounded-lg shadow-lg p-6 mb-6">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold text-gray-800">Sistema de Pagos</h1>
              <p className="text-gray-600">CuerpoSano Gimnasio</p>
            </div>
            <DollarSign className="w-12 h-12 text-indigo-600" />
          </div>
          
          {/* Progress Bar */}
          <div className="mt-6 flex items-center justify-between">
            {[1, 2, 3, 4].map((s) => (
              <div key={s} className="flex items-center flex-1">
                <div className={`w-10 h-10 rounded-full flex items-center justify-center font-bold ${
                  step >= s ? 'bg-indigo-600 text-white' : 'bg-gray-300 text-gray-600'
                }`}>
                  {s}
                </div>
                {s < 4 && (
                  <div className={`flex-1 h-1 mx-2 ${
                    step > s ? 'bg-indigo-600' : 'bg-gray-300'
                  }`} />
                )}
              </div>
            ))}
          </div>
          <div className="mt-2 flex justify-between text-xs text-gray-600">
            <span>Miembro</span>
            <span>Membresía</span>
            <span>Pago</span>
            <span>Confirmación</span>
          </div>
        </div>

        {/* Step 1: Seleccionar Miembro */}
        {step === 1 && (
          <div className="bg-white rounded-lg shadow-lg p-6">
            <h2 className="text-2xl font-bold text-gray-800 mb-4 flex items-center">
              <User className="mr-2" /> Seleccionar Miembro
            </h2>
            <div className="space-y-3">
              {members.map((member) => (
                <div
                  key={member.idMiembro}
                  onClick={() => setSelectedMember(member)}
                  className={`p-4 border-2 rounded-lg cursor-pointer transition-all ${
                    selectedMember?.idMiembro === member.idMiembro
                      ? 'border-indigo-600 bg-indigo-50'
                      : 'border-gray-300 hover:border-indigo-400'
                  }`}
                >
                  <div className="flex justify-between items-center">
                    <div>
                      <p className="font-bold text-gray-800">{member.nombre}</p>
                      <p className="text-sm text-gray-600">DNI: {member.dni}</p>
                      <p className="text-sm text-gray-600">{member.email}</p>
                    </div>
                    <span className={`px-3 py-1 rounded-full text-xs font-bold ${
                      member.estado === 'activo' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                    }`}>
                      {member.estado}
                    </span>
                  </div>
                </div>
              ))}
            </div>
            <button
              onClick={() => setStep(2)}
              disabled={!selectedMember}
              className="mt-6 w-full bg-indigo-600 text-white py-3 rounded-lg font-bold hover:bg-indigo-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
            >
              Continuar
            </button>
          </div>
        )}

        {/* Step 2: Seleccionar Membresía y Descuento */}
        {step === 2 && (
          <div className="bg-white rounded-lg shadow-lg p-6">
            <h2 className="text-2xl font-bold text-gray-800 mb-4 flex items-center">
              <Calendar className="mr-2" /> Seleccionar Membresía
            </h2>
            <div className="grid md:grid-cols-2 gap-4 mb-6">
              {membershipTypes.map((membership) => (
                <div
                  key={membership.idTipo}
                  onClick={() => setSelectedMembership(membership)}
                  className={`p-4 border-2 rounded-lg cursor-pointer transition-all ${
                    selectedMembership?.idTipo === membership.idTipo
                      ? 'border-indigo-600 bg-indigo-50'
                      : 'border-gray-300 hover:border-indigo-400'
                  }`}
                >
                  <h3 className="font-bold text-lg text-gray-800">{membership.nombre}</h3>
                  <p className="text-sm text-gray-600 mb-2">{membership.descripcion}</p>
                  <div className="flex justify-between items-center">
                    <span className="text-2xl font-bold text-indigo-600">${membership.precioBase}</span>
                    <span className="text-sm text-gray-600">{membership.duracionDias} días</span>
                  </div>
                </div>
              ))}
            </div>

            <h3 className="text-xl font-bold text-gray-800 mb-3 flex items-center">
              <Tag className="mr-2" /> Aplicar Descuento (Opcional)
            </h3>
            <div className="space-y-2 mb-6">
              <div
                onClick={() => setSelectedDiscount(null)}
                className={`p-3 border-2 rounded-lg cursor-pointer transition-all ${
                  !selectedDiscount ? 'border-indigo-600 bg-indigo-50' : 'border-gray-300 hover:border-indigo-400'
                }`}
              >
                <p className="font-bold text-gray-800">Sin descuento</p>
              </div>
              {discounts.map((discount) => (
                <div
                  key={discount.idDescuento}
                  onClick={() => setSelectedDiscount(discount)}
                  className={`p-3 border-2 rounded-lg cursor-pointer transition-all ${
                    selectedDiscount?.idDescuento === discount.idDescuento
                      ? 'border-indigo-600 bg-indigo-50'
                      : 'border-gray-300 hover:border-indigo-400'
                  }`}
                >
                  <div className="flex justify-between items-center">
                    <div>
                      <p className="font-bold text-gray-800">{discount.descripcion}</p>
                      <p className="text-sm text-gray-600">{discount.tipo}</p>
                    </div>
                    <span className="text-lg font-bold text-green-600">-{discount.porcentaje}%</span>
                  </div>
                </div>
              ))}
            </div>

            {selectedMembership && (
              <div className="bg-gray-100 p-4 rounded-lg mb-4">
                <div className="flex justify-between items-center text-lg">
                  <span className="font-bold text-gray-800">Total a Pagar:</span>
                  <div className="text-right">
                    {selectedDiscount && (
                      <p className="text-sm text-gray-600 line-through">${selectedMembership.precioBase}</p>
                    )}
                    <p className="text-2xl font-bold text-indigo-600">${calculateTotal()}</p>
                  </div>
                </div>
              </div>
            )}

            <div className="flex gap-3">
              <button
                onClick={() => setStep(1)}
                className="flex-1 bg-gray-300 text-gray-800 py-3 rounded-lg font-bold hover:bg-gray-400 transition-colors"
              >
                Atrás
              </button>
              <button
                onClick={() => setStep(3)}
                disabled={!selectedMembership}
                className="flex-1 bg-indigo-600 text-white py-3 rounded-lg font-bold hover:bg-indigo-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
              >
                Continuar
              </button>
            </div>
          </div>
        )}

        {/* Step 3: Método de Pago */}
        {step === 3 && (
          <div className="bg-white rounded-lg shadow-lg p-6">
            <h2 className="text-2xl font-bold text-gray-800 mb-4 flex items-center">
              <Wallet className="mr-2" /> Método de Pago
            </h2>

            {/* Payment Method Selection */}
            <div className="grid md:grid-cols-3 gap-3 mb-6">
              {[
                { id: 'Efectivo', icon: DollarSign, label: 'Efectivo' },
                { id: 'Tarjeta de Crédito', icon: CreditCard, label: 'Tarjeta Crédito' },
                { id: 'Tarjeta de Débito', icon: CreditCard, label: 'Tarjeta Débito' },
                { id: 'Transferencia Bancaria', icon: Building, label: 'Transferencia' },
                { id: 'QR', icon: QrCode, label: 'Código QR' }
              ].map((method) => (
                <div
                  key={method.id}
                  onClick={() => {
                    setPaymentMethod(method.id);
                    if (method.id === 'QR') generateQR();
                  }}
                  className={`p-4 border-2 rounded-lg cursor-pointer transition-all flex flex-col items-center ${
                    paymentMethod === method.id
                      ? 'border-indigo-600 bg-indigo-50'
                      : 'border-gray-300 hover:border-indigo-400'
                  }`}
                >
                  <method.icon className="w-8 h-8 mb-2 text-indigo-600" />
                  <span className="text-sm font-bold text-gray-800">{method.label}</span>
                </div>
              ))}
            </div>

            {/* Payment Forms */}
            {paymentMethod === 'Efectivo' && (
              <div className="bg-blue-50 p-4 rounded-lg mb-4">
                <p className="text-gray-800 font-bold mb-2">Pago en Efectivo</p>
                <p className="text-gray-600 text-sm">El pago en efectivo debe realizarse en la recepción del gimnasio.</p>
                <div className="mt-3">
                  <label className="block text-sm font-bold text-gray-700 mb-1">Número de Referencia</label>
                  <input
                    type="text"
                    value={paymentData.reference}
                    onChange={(e) => setPaymentData({ ...paymentData, reference: e.target.value })}
                    placeholder="REF-XXXXXX (Opcional)"
                    className="w-full p-2 border border-gray-300 rounded-lg"
                  />
                </div>
              </div>
            )}

            {(paymentMethod === 'Tarjeta de Crédito' || paymentMethod === 'Tarjeta de Débito') && (
              <div className="space-y-4 mb-4">
                <div>
                  <label className="block text-sm font-bold text-gray-700 mb-1">Número de Tarjeta</label>
                  <input
                    type="text"
                    value={paymentData.cardNumber}
                    onChange={(e) => setPaymentData({ ...paymentData, cardNumber: e.target.value })}
                    placeholder="1234 5678 9012 3456"
                    maxLength="19"
                    className="w-full p-3 border border-gray-300 rounded-lg"
                  />
                </div>
                <div>
                  <label className="block text-sm font-bold text-gray-700 mb-1">Nombre en la Tarjeta</label>
                  <input
                    type="text"
                    value={paymentData.cardName}
                    onChange={(e) => setPaymentData({ ...paymentData, cardName: e.target.value })}
                    placeholder="JUAN PEREZ"
                    className="w-full p-3 border border-gray-300 rounded-lg"
                  />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-bold text-gray-700 mb-1">Fecha de Expiración</label>
                    <input
                      type="text"
                      value={paymentData.expiryDate}
                      onChange={(e) => setPaymentData({ ...paymentData, expiryDate: e.target.value })}
                      placeholder="MM/AA"
                      maxLength="5"
                      className="w-full p-3 border border-gray-300 rounded-lg"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-bold text-gray-700 mb-1">CVV</label>
                    <input
                      type="text"
                      value={paymentData.cvv}
                      onChange={(e) => setPaymentData({ ...paymentData, cvv: e.target.value })}
                      placeholder="123"
                      maxLength="4"
                      className="w-full p-3 border border-gray-300 rounded-lg"
                    />
                  </div>
                </div>
              </div>
            )}

            {paymentMethod === 'Transferencia Bancaria' && (
              <div className="bg-blue-50 p-4 rounded-lg mb-4">
                <p className="text-gray-800 font-bold mb-2">Datos Bancarios</p>
                <div className="space-y-2 text-sm text-gray-700">
                  <p><strong>Banco:</strong> Banco Nacional</p>
                  <p><strong>Titular:</strong> CuerpoSano S.A.</p>
                  <p><strong>Cuenta:</strong> 1234-5678-9012-3456</p>
                  <p><strong>CBU:</strong> 0123456789012345678901</p>
                </div>
                <div className="mt-3">
                  <label className="block text-sm font-bold text-gray-700 mb-1">Número de Comprobante</label>
                  <input
                    type="text"
                    value={paymentData.reference}
                    onChange={(e) => setPaymentData({ ...paymentData, reference: e.target.value })}
                    placeholder="Número de comprobante"
                    className="w-full p-2 border border-gray-300 rounded-lg"
                  />
                </div>
              </div>
            )}

            {paymentMethod === 'QR' && qrCode && (
              <div className="bg-blue-50 p-6 rounded-lg mb-4 text-center">
                <p className="text-gray-800 font-bold mb-4">Escanea el código QR para pagar</p>
                <div className="bg-white p-4 inline-block rounded-lg">
                  <div className="w-64 h-64 bg-gradient-to-br from-indigo-100 to-blue-100 flex items-center justify-center rounded-lg">
                    <QrCode className="w-32 h-32 text-indigo-600" />
                  </div>
                  <p className="text-xs text-gray-600 mt-2">Simulación de QR Code</p>
                  <p className="text-xs text-gray-500 font-mono mt-1 break-all">{qrCode.substring(0, 30)}...</p>
                </div>
                <p className="text-sm text-gray-600 mt-4">Monto: ${calculateTotal()}</p>
                <p className="text-xs text-gray-500 mt-1">Ref: {paymentData.reference}</p>
              </div>
            )}

            {/* Summary */}
            <div className="bg-gray-100 p-4 rounded-lg mb-4">
              <h3 className="font-bold text-gray-800 mb-2">Resumen del Pago</h3>
              <div className="space-y-1 text-sm">
                <div className="flex justify-between">
                  <span className="text-gray-600">Miembro:</span>
                  <span className="font-bold">{selectedMember?.nombre}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600">Membresía:</span>
                  <span className="font-bold">{selectedMembership?.nombre}</span>
                </div>
                {selectedDiscount && (
                  <div className="flex justify-between text-green-600">
                    <span>Descuento:</span>
                    <span className="font-bold">-{selectedDiscount.porcentaje}%</span>
                  </div>
                )}
                <div className="flex justify-between text-lg pt-2 border-t border-gray-300">
                  <span className="font-bold text-gray-800">Total:</span>
                  <span className="font-bold text-indigo-600">${calculateTotal()}</span>
                </div>
              </div>
            </div>

            <div className="flex gap-3">
              <button
                onClick={() => setStep(2)}
                className="flex-1 bg-gray-300 text-gray-800 py-3 rounded-lg font-bold hover:bg-gray-400 transition-colors"
              >
                Atrás
              </button>
              <button
                onClick={processPayment}
                disabled={!paymentMethod || processing}
                className="flex-1 bg-indigo-600 text-white py-3 rounded-lg font-bold hover:bg-indigo-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors flex items-center justify-center"
              >
                {processing ? (
                  <>
                    <Clock className="w-5 h-5 mr-2 animate-spin" />
                    Procesando...
                  </>
                ) : (
                  'Procesar Pago'
                )}
              </button>
            </div>
          </div>
        )}

        {/* Step 4: Confirmación */}
        {step === 4 && paymentResult && (
          <div className="bg-white rounded-lg shadow-lg p-6">
            <div className="text-center">
              {paymentResult.success ? (
                <>
                  <CheckCircle className="w-20 h-20 text-green-500 mx-auto mb-4" />
                  <h2 className="text-3xl font-bold text-gray-800 mb-2">¡Pago Exitoso!</h2>
                  <p className="text-gray-600 mb-6">{paymentResult.message}</p>
                </>
              ) : (
                <>
                  <XCircle className="w-20 h-20 text-red-500 mx-auto mb-4" />
                  <h2 className="text-3xl font-bold text-gray-800 mb-2">Pago Rechazado</h2>
                  <p className="text-gray-600 mb-6">{paymentResult.message}</p>
                </>
              )}

              <div className="bg-gray-100 p-6 rounded-lg mb-6 text-left">
                <h3 className="font-bold text-gray-800 mb-4">Detalles del Pago</h3>
                <div className="space-y-2 text-sm">
                  <div className="flex justify-between">
                    <span className="text-gray-600">Miembro:</span>
                    <span className="font-bold">{selectedMember?.nombre}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">Membresía:</span>
                    <span className="font-bold">{selectedMembership?.nombre}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">Método de Pago:</span>
                    <span className="font-bold">{paymentResult.data.metodoPago}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">Referencia:</span>
                    <span className="font-bold">{paymentResult.data.referencia}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">Fecha:</span>
                    <span className="font-bold">{paymentResult.data.fechaPago}</span>
                  </div>
                  <div className="flex justify-between text-lg pt-2 border-t border-gray-300">
                    <span className="font-bold text-gray-800">Monto Pagado:</span>
                    <span className="font-bold text-green-600">${paymentResult.data.monto}</span>
                  </div>
                </div>
              </div>

              <button
                onClick={resetPayment}
                className="w-full bg-indigo-600 text-white py-3 rounded-lg font-bold hover:bg-indigo-700 transition-colors"
              >
                Realizar Otro Pago
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default PaymentSystem;