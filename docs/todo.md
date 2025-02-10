# V2

-   Añadir un total de cuanto se lleva en los pagos (vender boleto y pagar boleto)
-   Mensaje a cliente
-   Mensajes personalizados (configurable)
    -   Se pueden enviar manual o automatico

---

# Detected Issues

-   SellTicketsAction, manejo de grandes cantidades de boletos, tal vez por paginacion y mantener el tracking, en un componente unico de livewire para manejar ese caso
    -   Solucion 1: Usar steps para no re-renderizar los boletos y mantener el state en un array [id, number]
