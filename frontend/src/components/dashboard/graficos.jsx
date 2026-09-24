import { Bar, BarChart, CartesianGrid, ReferenceLine, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

/** Cores dos gráficos (paleta validada para daltonismo). */
export const CORES = {
  serie: '#2a78d6',
  comparacao: '#b4b2a9',
  /** Uma cor por categoria, sempre na mesma ordem (a cor segue o canal/plano, não a posição no ranking). */
  categorias: ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300'],
  grade: '#e1e0d9',
  eixo: '#898781',
  referencia: '#d03b3b',
};

export const eixo = { stroke: CORES.eixo, fontSize: 12, tickLine: false, axisLine: { stroke: CORES.grade } };

export function DicaGrafico({ active, payload, label, formatarRotulo = (valor) => valor, linhas }) {
  if (!active || !payload?.length) return null;

  return (
    <div className="grafico-dica">
      <strong>{formatarRotulo(label)}</strong>
      {linhas(payload[0].payload, payload).map(([nome, valor, cor]) => (
        <div key={nome} className="grafico-dica__linha">
          {cor && <span className="grafico-dica__cor" style={{ background: cor }} />}
          <span>{nome}</span>
          <strong>{valor}</strong>
        </div>
      ))}
    </div>
  );
}

/** Barras horizontais de uma série só, ordenadas pelo maior valor. */
export function BarrasHorizontais({ dados, rotulo, valor, formatar, dica, referencia }) {
  const altura = Math.max(dados.length * 36 + 40, 160);

  return (
    <ResponsiveContainer width="100%" height={altura}>
      <BarChart data={dados} layout="vertical" margin={{ top: 4, right: 24, bottom: 4, left: 8 }}>
        <CartesianGrid horizontal={false} stroke={CORES.grade} />
        <XAxis type="number" {...eixo} tickFormatter={formatar} />
        <YAxis type="category" dataKey={rotulo} {...eixo} width={150} />
        <Tooltip cursor={{ fill: '#eef3f9' }} content={<DicaGrafico linhas={dica} />} />
        {referencia && (
          <ReferenceLine
            x={referencia.valor}
            stroke={CORES.referencia}
            strokeWidth={1.5}
            label={{ value: referencia.texto, position: 'top', fill: CORES.referencia, fontSize: 12 }}
          />
        )}
        <Bar dataKey={valor} fill={CORES.serie} barSize={14} radius={[0, 4, 4, 0]} />
      </BarChart>
    </ResponsiveContainer>
  );
}
