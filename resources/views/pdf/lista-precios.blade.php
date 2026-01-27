<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 4px; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
    <h3>Lista de Precios</h3>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Descripción</th>
                <th>Precio</th>
                <th>Min</th>
                <th>30CJ</th>
                <th>60CJ</th>
                <th>100CJ</th>
            </tr>
        </thead>
        <tbody>
            @foreach($productos as $p)
                <tr>
                    <td>{{ $p['Cod Max'] }}</td>
                    <td>{{ $p->Descripcion }}</td>
                    <td>{{ number_format($p->Precio, 0, ',', '.') }}</td>
                    <td>{{ $p->Minimo }}</td>
                    <td>{{ $p['30CJ'] }}</td>
                    <td>{{ $p['60CJ'] }}</td>
                    <td>{{ $p['100CJ'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
